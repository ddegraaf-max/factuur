<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Services\QuoteManager;
use App\Services\VatCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class QuoteController extends Controller
{
    public function __construct(protected QuoteManager $manager) {}

    public function index(Request $request): Response
    {
        // Verlopen offertes bijwerken zodra iemand de lijst opent.
        $this->manager->markExpired();

        $status = $request->input('status', 'all');
        $q = $request->input('q');

        $quotes = Quote::with('customer')
            ->forStatus($status)
            ->when($q, fn ($qb) => $qb->where(function ($w) use ($q) {
                $w->where('number', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('reference', 'like', "%{$q}%");
            }))
            ->orderByDesc('quote_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $quotes->getCollection()->transform(fn ($quote) => [
            'id' => $quote->id,
            'number' => $quote->number,
            'customer_name' => $quote->customer_name,
            'quote_date_label' => $quote->quote_date->translatedFormat('j M Y'),
            'valid_until_label' => $quote->valid_until->translatedFormat('j M Y'),
            'days_left' => $quote->days_left,
            'status' => $quote->status,
            'status_label' => $quote->status_label,
            'total' => (float) $quote->total,
            'converted' => (bool) $quote->converted_invoice_id,
        ]);

        return Inertia::render('Quotes/Index', [
            'quotes' => $quotes,
            'filters' => ['status' => $status, 'q' => $q],
            'counts' => [
                'all' => Quote::count(),
                'draft' => Quote::where('status', 'draft')->count(),
                'sent' => Quote::where('status', 'sent')->count(),
                'accepted' => Quote::where('status', 'accepted')->count(),
                'rejected' => Quote::where('status', 'rejected')->count(),
                'expired' => Quote::where('status', 'expired')->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Quotes/Form', array_merge($this->formData(), [
            'quote' => null,
            'preselect_customer_id' => $request->input('customer_id'),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $quote = $this->manager->create($data);

        if ($request->input('action') === 'send') {
            return $this->dispatchSend($quote);
        }

        return redirect()->route('quotes.show', $quote)->with('flash', 'Concept-offerte opgeslagen.');
    }

    public function show(Quote $quote): Response
    {
        $quote->load('lines', 'customer', 'invoice');

        return Inertia::render('Quotes/Show', [
            'quote' => array_merge($quote->toArray(), [
                'quote_date_label' => $quote->quote_date->translatedFormat('j M Y'),
                'valid_until_label' => $quote->valid_until->translatedFormat('j M Y'),
                'sent_at_label' => $quote->sent_at?->translatedFormat('j M Y, H:i'),
                'accepted_at_label' => $quote->accepted_at?->translatedFormat('j M Y'),
                'rejected_at_label' => $quote->rejected_at?->translatedFormat('j M Y'),
                'status_label' => $quote->status_label,
                'days_left' => $quote->days_left,
                'invoice' => $quote->invoice ? [
                    'id' => $quote->invoice->id,
                    'number' => $quote->invoice->number,
                    'status' => $quote->invoice->status,
                ] : null,
            ]),
            'company' => auth()->user()->company,
        ]);
    }

    public function edit(Quote $quote): Response|RedirectResponse
    {
        if (! in_array($quote->status, ['draft', 'sent'], true)) {
            return redirect()->route('quotes.show', $quote)
                ->withErrors(['quote' => 'Een geaccepteerde of afgewezen offerte kun je niet meer wijzigen.']);
        }

        $quote->load('lines');

        return Inertia::render('Quotes/Form', array_merge($this->formData(), [
            'quote' => $quote,
        ]));
    }

    public function update(Request $request, Quote $quote): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $this->manager->update($quote, $data);
        } catch (\DomainException $e) {
            return back()->withErrors(['quote' => $e->getMessage()]);
        }

        if ($request->input('action') === 'send') {
            return $this->dispatchSend($quote->fresh());
        }

        return redirect()->route('quotes.show', $quote)->with('flash', 'Offerte bijgewerkt.');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        if ($quote->status !== 'draft') {
            return back()->withErrors(['quote' => 'Alleen concepten kunnen worden verwijderd.']);
        }

        $quote->delete();

        return redirect()->route('quotes.index')->with('flash', 'Concept verwijderd.');
    }

    public function send(Quote $quote): RedirectResponse
    {
        return $this->dispatchSend($quote);
    }

    public function accept(Quote $quote): RedirectResponse
    {
        try {
            $this->manager->accept($quote);
        } catch (\DomainException $e) {
            return back()->withErrors(['quote' => $e->getMessage()]);
        }

        return back()->with('flash', 'Offerte gemarkeerd als geaccepteerd.');
    }

    public function reject(Quote $quote): RedirectResponse
    {
        try {
            $this->manager->reject($quote);
        } catch (\DomainException $e) {
            return back()->withErrors(['quote' => $e->getMessage()]);
        }

        return back()->with('flash', 'Offerte gemarkeerd als afgewezen.');
    }

    public function convert(Quote $quote): RedirectResponse
    {
        try {
            $invoice = $this->manager->convertToInvoice($quote);
        } catch (\DomainException $e) {
            return back()->withErrors(['quote' => $e->getMessage()]);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('flash', 'Concept-factuur aangemaakt uit offerte '.$quote->number.'. Controleer en verstuur hem.');
    }

    public function pdf(Quote $quote): HttpResponse
    {
        $quote->load('lines');

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'company' => auth()->user()->company,
        ])->setPaper('a4');

        return $pdf->stream(($quote->number ?: "concept-{$quote->id}").'.pdf');
    }

    protected function dispatchSend(Quote $quote): RedirectResponse
    {
        try {
            $this->manager->send($quote);
        } catch (\DomainException $e) {
            return back()->withErrors(['quote' => $e->getMessage()]);
        }

        $fresh = $quote->fresh();
        $message = $fresh->customer_email
            ? "Offerte {$fresh->number} verstuurd naar {$fresh->customer_email}."
            : "Offerte {$fresh->number} vastgelegd. Deze klant heeft geen e-mailadres — download de PDF om hem zelf te versturen.";

        return redirect()->route('quotes.show', $fresh)->with('flash', $message);
    }

    protected function formData(): array
    {
        $company = auth()->user()->company;

        return [
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'address_line', 'postal_code', 'city', 'country', 'vat_number', 'kvk_number', 'email']),
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'description', 'unit', 'price', 'vat_rate']),
            'vat_rates' => VatCalculator::availableRates(),
            'price_mode' => $company?->price_mode ?? 'excl',
            'default_valid_days' => $company?->quote_valid_days ?? 30,
        ];
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'quote_date' => ['required', 'date'],
            'valid_days' => ['required', 'integer', 'min:1', 'max:365'],
            'reference' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.details' => ['nullable', 'string'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.unit' => ['nullable', 'string', 'max:30'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.vat_rate' => ['required', 'numeric', 'in:0,9,21'],
            'action' => ['nullable', 'in:draft,send'],
        ]);
    }
}
