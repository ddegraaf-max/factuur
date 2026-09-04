<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Services\QuoteManager;
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

    /**
     * Offerte uit tekst: een geplakte offertetekst (bijv. geschreven met
     * Claude) wordt door de AI omgezet naar formuliervelden. Er wordt hier
     * niets opgeslagen — de gebruiker controleert het resultaat in het
     * formulier.
     */
    public function parseText(Request $request, \App\Services\QuoteTextScanService $scanner): \Illuminate\Http\JsonResponse
    {
        abort_unless($scanner->enabled(), 404);

        if (! $request->user()->company->hasAiAccess()) {
            return response()->json([
                'message' => __('Offerte uit tekst zit in het Slim-abonnement. Upgrade via Instellingen → Abonnement.'),
            ], 403);
        }

        if ($request->user()->company->aiLimitReached()) {
            return response()->json([
                'message' => __('Het maandelijkse AI-tegoed is opgebruikt (fair use). Volgende maand staat de teller weer op nul.'),
            ], 429);
        }

        $data = $request->validate([
            'text' => ['required', 'string', 'max:20000'],
        ], [
            'text.required' => __('Plak eerst de offertetekst.'),
            'text.max' => __('De tekst is te lang (maximaal 20.000 tekens).'),
        ]);

        try {
            $result = $scanner->scan($data['text']);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        \App\Models\AiUsageEvent::record($request->user()->company_id, 'quote_parse', 'form');

        // Herkende klantnaam koppelen aan een bestaande klant (alleen bij een
        // eenduidige match — anders kiest de gebruiker zelf).
        $result['customer_id'] = null;
        if ($result['customer_name']) {
            $needle = mb_strtolower(trim($result['customer_name']));
            $matches = Customer::get(['id', 'name'])->filter(function ($c) use ($needle) {
                $name = mb_strtolower($c->name);

                return str_contains($name, $needle) || str_contains($needle, $name);
            });
            if ($matches->count() === 1) {
                $result['customer_id'] = $matches->first()->id;
            }
        }

        return response()->json(['result' => $result]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $quote = $this->manager->create($data);

        if ($request->input('action') === 'send') {
            return $this->dispatchSend($quote);
        }

        return redirect()->route('quotes.show', $quote)->with('flash', __('Concept-offerte opgeslagen.'));
    }

    public function show(Quote $quote): Response
    {
        $quote->load('lines', 'customer', 'invoice', 'installments.invoice', 'attachments');

        // Termijnfacturen: eerstvolgende open termijn (factureren op volgorde).
        $nextInstallmentId = $quote->installments->firstWhere('invoice_id', null)?->id;

        return Inertia::render('Quotes/Show', [
            'quote' => array_merge($quote->toArray(), [
                'quote_date_label' => $quote->quote_date->translatedFormat('j M Y'),
                'valid_until_label' => $quote->valid_until->translatedFormat('j M Y'),
                'sent_at_label' => $quote->sent_at?->translatedFormat('j M Y, H:i'),
                'accepted_at_label' => $quote->accepted_at?->translatedFormat('j M Y'),
                'rejected_at_label' => $quote->rejected_at?->translatedFormat('j M Y'),
                'status_label' => $quote->status_label,
                'days_left' => $quote->days_left,
                'brand_profile_name' => $quote->brandProfile?->name,
                'signed_at_label' => $quote->signed_at?->translatedFormat('j F Y, H:i'),
                'accept_mail_sent_at_label' => $quote->accept_mail_sent_at?->translatedFormat('j M Y, H:i'),
                'portal_url' => $quote->portalUrl(),
                'invoice' => $quote->invoice ? [
                    'id' => $quote->invoice->id,
                    'number' => $quote->invoice->number,
                    'status' => $quote->invoice->status,
                ] : null,
                'attachments' => $quote->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'filename' => $a->filename,
                    'kind' => $a->kind,
                    'size_formatted' => $a->size_formatted,
                    'for_customer' => (bool) $a->for_customer,
                    'uploaded_at_label' => $a->created_at?->translatedFormat('j M Y'),
                ]),
                'installments' => $quote->installments->map(fn ($i) => [
                    'id' => $i->id,
                    'description' => $i->description,
                    'percentage' => (float) $i->percentage,
                    'amount' => (float) $i->amount,
                    'invoice' => $i->invoice ? [
                        'id' => $i->invoice->id,
                        'number' => $i->invoice->number,
                        'status' => $i->invoice->status,
                    ] : null,
                    'is_next' => $i->id === $nextInstallmentId,
                ])->values(),
                'can_installments' => in_array($quote->status, ['sent', 'accepted'], true)
                    && ! $quote->converted_invoice_id,
                'installments_locked' => $quote->installments->whereNotNull('invoice_id')->isNotEmpty(),
            ]),
            // De offertevoorvertoning toont de huisstijl van de handelsnaam.
            'company' => $quote->brandedCompany(),
        ]);
    }

    public function edit(Quote $quote): Response|RedirectResponse
    {
        if (! in_array($quote->status, ['draft', 'sent'], true)) {
            return redirect()->route('quotes.show', $quote)
                ->withErrors(['quote' => __('Een geaccepteerde of afgewezen offerte kun je niet meer wijzigen.')]);
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

        return redirect()->route('quotes.show', $quote)->with('flash', __('Offerte bijgewerkt.'));
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        if ($quote->status !== 'draft') {
            return back()->withErrors(['quote' => __('Alleen concepten kunnen worden verwijderd.')]);
        }

        $quote->delete();

        return redirect()->route('quotes.index')->with('flash', __('Concept verwijderd.'));
    }

    public function send(Quote $quote): RedirectResponse
    {
        return $this->dispatchSend($quote);
    }

    public function accept(Request $request, Quote $quote): RedirectResponse
    {
        try {
            $this->manager->accept($quote);
        } catch (\DomainException $e) {
            return back()->withErrors(['quote' => $e->getMessage()]);
        }

        $flash = __('Offerte gemarkeerd als geaccepteerd.');

        // Vinkje "Bevestiging mailen naar de klant" (bijv. akkoord per telefoon).
        if ($request->boolean('send_confirmation')) {
            try {
                $this->manager->sendAcceptConfirmation($quote->fresh());
                $flash .= ' ' . __('Bevestiging gemaild naar :email.', ['email' => $quote->customer_email]);
            } catch (\DomainException $e) {
                $flash .= ' ' . __('Geen bevestiging: :reason', ['reason' => $e->getMessage()]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Bevestiging akkoord mislukt', ['quote' => $quote->id, 'error' => $e->getMessage()]);
                $flash .= ' ' . __('De bevestiging kon niet worden gemaild — probeer het straks via "Bevestiging mailen".');
            }
        }

        return back()->with('flash', $flash);
    }

    /** Bevestiging van het akkoord (opnieuw) naar de klant mailen. */
    public function confirm(Quote $quote): RedirectResponse
    {
        try {
            $this->manager->sendAcceptConfirmation($quote, force: true);
        } catch (\DomainException $e) {
            return back()->withErrors(['quote' => $e->getMessage()]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Bevestiging akkoord (handmatig) mislukt', ['quote' => $quote->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['quote' => __('Versturen is niet gelukt. Probeer het later opnieuw.')]);
        }

        return back()->with('flash', __('Bevestiging gemaild naar :email.', ['email' => $quote->customer_email]));
    }

    public function reject(Quote $quote): RedirectResponse
    {
        try {
            $this->manager->reject($quote);
        } catch (\DomainException $e) {
            return back()->withErrors(['quote' => $e->getMessage()]);
        }

        return back()->with('flash', __('Offerte gemarkeerd als afgewezen.'));
    }

    /** Afgewezen of verlopen offerte opnieuw aanbieden: terug naar concept, daarna aanpassen en versturen. */
    public function reopen(Quote $quote): RedirectResponse
    {
        try {
            $this->manager->reopen($quote);
        } catch (\DomainException $e) {
            return back()->withErrors(['quote' => $e->getMessage()]);
        }

        return back()->with('flash', __('Offerte teruggezet naar concept met een nieuwe geldigheidsdatum. Pas hem aan als je wilt en verstuur hem opnieuw.'));
    }

    public function convert(Quote $quote): RedirectResponse
    {
        try {
            $invoice = $this->manager->convertToInvoice($quote);
        } catch (\DomainException $e) {
            return back()->withErrors(['quote' => $e->getMessage()]);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('flash', __('Concept-factuur aangemaakt uit offerte :number. Controleer en verstuur hem.', ['number' => $quote->number]));
    }

    public function pdf(Quote $quote): HttpResponse
    {
        $quote->load('lines');

        $pdf = \App\Support\DocumentLocale::using($quote->language, fn () => Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'company' => $quote->brandedCompany(),
        ])->setPaper('a4'));

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
            ? __('Offerte :number verstuurd naar :email.', ['number' => $fresh->number, 'email' => $fresh->customer_email])
            : __('Offerte :number vastgelegd. Deze klant heeft geen e-mailadres — download de PDF om hem zelf te versturen.', ['number' => $fresh->number]);

        return redirect()->route('quotes.show', $fresh)->with('flash', $message);
    }

    protected function formData(): array
    {
        $company = auth()->user()->company;

        return [
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'address_line', 'postal_code', 'city', 'country', 'vat_number', 'kvk_number', 'email']),
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'description', 'unit', 'price', 'vat_rate']),
            'vat_rates' => \App\Support\Market::vatRateOptions(),
            'default_vat_rate' => \App\Support\Market::defaultVatRate(),
            'price_mode' => $company?->price_mode ?? 'excl',
            'default_valid_days' => $company?->quote_valid_days ?? 30,
            'brand_profiles' => \App\Models\BrandProfile::orderBy('name')->get(['id', 'name']),
            // "Offerte uit tekst" (AI) — Slim-abonnement (of proef/demo/vrijgesteld).
            'ai_enabled' => app(\App\Services\QuoteTextScanService::class)->availableFor($company),
            'ai_locked' => app(\App\Services\QuoteTextScanService::class)->enabled() && $company !== null && ! $company->hasAiAccess(),
        ];
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            // Hoe de prijzen in dít formulier zijn ingetypt (schakelaar op het
            // formulier); zonder waarde geldt de bedrijfsinstelling.
            'price_mode' => ['nullable', 'in:excl,incl'],
            // De manager controleert dat het profiel van het eigen bedrijf is.
            'brand_profile_id' => ['nullable', 'integer', 'exists:brand_profiles,id'],
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
            // Negatief mag: een korting of verrekening als losse regel is
            // gangbaar. Alleen het offertetotaal mag niet onder nul (zie onder).
            'lines.*.unit_price' => ['required', 'numeric', 'min:-1000000', 'max:1000000'],
            'lines.*.vat_rate' => ['required', 'numeric', 'in:' . implode(',', \App\Support\Market::vatRates())],
            'lines.*.discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'action' => ['nullable', 'in:draft,send'],
        ], [
            'customer_id.required' => __('Kies een klant voor deze offerte.'),
            'customer_id.exists' => __('Kies een klant voor deze offerte.'),
            'quote_date.required' => __('Vul een offertedatum in.'),
            'quote_date.date' => __('Vul een geldige offertedatum in.'),
            'valid_days.required' => __('Vul in hoeveel dagen de offerte geldig is.'),
            'valid_days.integer' => __('De geldigheid moet een heel aantal dagen zijn.'),
            'valid_days.min' => __('De offerte moet minstens 1 dag geldig zijn.'),
            'valid_days.max' => __('De geldigheid kan maximaal 365 dagen zijn.'),
            'lines.required' => __('Voeg minstens één offerteregel toe.'),
            'lines.min' => __('Voeg minstens één offerteregel toe.'),
            'lines.*.description.required' => __('Vul een omschrijving in.'),
            'lines.*.description.max' => __('De omschrijving mag maximaal 500 tekens lang zijn.'),
            'lines.*.quantity.required' => __('Vul een aantal in.'),
            'lines.*.quantity.numeric' => __('Het aantal moet een getal zijn.'),
            'lines.*.quantity.min' => __('Het aantal kan niet negatief zijn.'),
            'lines.*.unit_price.required' => __('Vul een prijs in.'),
            'lines.*.unit_price.numeric' => __('De prijs moet een getal zijn (gebruik een punt als decimaalteken).'),
            'lines.*.unit_price.min' => __('De prijs valt buiten het toegestane bereik.'),
            'lines.*.unit_price.max' => __('De prijs valt buiten het toegestane bereik.'),
            'lines.*.vat_rate.required' => __('Kies een btw-tarief.'),
            'lines.*.vat_rate.in' => __('Kies een geldig btw-tarief (:rates%).', ['rates' => implode(', ', \App\Support\Market::vatRates())]),
            'lines.*.discount_pct.numeric' => __('De korting moet een getal zijn.'),
            'lines.*.discount_pct.min' => __('De korting kan niet negatief zijn.'),
            'lines.*.discount_pct.max' => __('De korting kan maximaal 100% zijn.'),
        ]);

        // Losse regels mogen negatief zijn, maar de optelsom niet. Het teken
        // van de som is in beide prijsmodi (incl./excl.) gelijk.
        $sum = collect($data['lines'])->sum(function ($line) {
            $factor = 1 - min(100, max(0, (float) ($line['discount_pct'] ?? 0))) / 100;

            return (float) ($line['quantity'] ?? 0) * (float) ($line['unit_price'] ?? 0) * $factor;
        });

        if ($sum < -0.005) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lines' => __('Het offertetotaal kan niet negatief zijn.'),
            ]);
        }

        return $data;
    }
}
