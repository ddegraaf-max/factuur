<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Services\InvoiceManager;
use App\Services\VatCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceManager $manager) {}

    public function index(Request $request): Response
    {
        // Auto-refresh overdue status on listing (skip credit notes + incasso)
        Invoice::where('status', 'sent')
            ->where('is_credit', false)
            ->whereDate('due_date', '<', now())
            ->update(['status' => 'overdue']);

        $status = $request->input('status', 'all');
        $q = $request->input('q');

        $invoices = Invoice::with('customer')
            ->withCount('attachments')
            ->forStatus($status)
            ->when($q, fn ($qb) => $qb->where(function ($w) use ($q) {
                $w->where('number', 'like', "%{$q}%")
                  ->orWhere('customer_name', 'like', "%{$q}%")
                  ->orWhere('reference', 'like', "%{$q}%");
            }))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $invoices->getCollection()->transform(fn ($i) => [
            'id' => $i->id,
            'number' => $i->number,
            'customer_name' => $i->customer_name,
            'invoice_date' => $i->invoice_date->format('Y-m-d'),
            'invoice_date_label' => $i->invoice_date->translatedFormat('j M Y'),
            'due_date_label' => $i->due_date?->translatedFormat('j M Y'),
            'status' => $i->status,
            'is_credit' => (bool) $i->is_credit,
            'days_overdue' => $i->days_overdue,
            'total' => (float) $i->total,
            'paid_total' => (float) $i->paid_total,
            'attachments_count' => $i->attachments_count,
        ]);

        // Counts per status for the filter chips (regular invoices only, credit notes separate)
        $counts = [
            'all' => Invoice::count(),
            'draft' => Invoice::regular()->where('status', 'draft')->count(),
            'sent' => Invoice::regular()->where('status', 'sent')->count(),
            'partial' => Invoice::regular()->where('status', 'partial')->count(),
            'overdue' => Invoice::regular()->where('status', 'overdue')->count(),
            'incasso' => Invoice::regular()->where('status', 'incasso')->count(),
            'paid' => Invoice::regular()->where('status', 'paid')->count(),
            'creditnota' => Invoice::credit()->count(),
        ];

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => ['status' => $status, 'q' => $q],
            'counts' => $counts,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Invoices/Form', [
            'invoice' => null,
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'address_line', 'postal_code', 'city', 'country', 'vat_number', 'kvk_number', 'email', 'payment_terms']),
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'description', 'unit', 'price', 'vat_rate']),
            'vat_rates' => VatCalculator::availableRates(),
            'preselect_customer_id' => $request->input('customer_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $invoice = $this->manager->create($data);

        if ($request->input('action') === 'send') {
            $this->manager->send($invoice);
            return redirect()->route('invoices.show', $invoice)->with('flash', "Factuur {$invoice->number} verstuurd.");
        }

        return redirect()->route('invoices.show', $invoice)->with('flash', 'Concept opgeslagen.');
    }

    public function show(Invoice $invoice): Response
    {
        $invoice->load('lines', 'payments', 'customer', 'attachments', 'creditNotes');
        if ($invoice->is_credit) {
            $invoice->load('originalInvoice');
        }
        $company = auth()->user()->company;

        return Inertia::render('Invoices/Show', [
            'invoice' => array_merge($invoice->toArray(), [
                'invoice_date_label' => $invoice->invoice_date->translatedFormat('j M Y'),
                'due_date_label' => $invoice->due_date?->translatedFormat('j M Y'),
                'sent_at_label' => $invoice->sent_at?->translatedFormat('j M Y, H:i'),
                'incasso_sent_at_label' => $invoice->incasso_sent_at?->translatedFormat('j M Y'),
                'days_overdue' => $invoice->days_overdue,
                'remaining' => $invoice->remaining_amount,
                'attachments' => $invoice->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'filename' => $a->filename,
                    'kind' => $a->kind,
                    'size_formatted' => $a->size_formatted,
                    'uploaded_at_label' => $a->created_at?->translatedFormat('j M Y'),
                ]),
                'credit_notes' => $invoice->creditNotes->map(fn ($c) => [
                    'id' => $c->id,
                    'number' => $c->number,
                    'status' => $c->status,
                    'total' => (float) $c->total,
                    'invoice_date_label' => $c->invoice_date->translatedFormat('j M Y'),
                ]),
            ]),
            'company' => $company,
        ]);
    }

    public function edit(Invoice $invoice): Response
    {
        if ($invoice->status !== 'draft') {
            return Inertia::render('Invoices/Show', [
                'invoice' => $invoice->load('lines', 'payments'),
                'flash' => ['error' => 'Verstuurde facturen kunnen niet worden gewijzigd. Maak een creditnota aan.'],
            ]);
        }

        $invoice->load('lines');

        return Inertia::render('Invoices/Form', [
            'invoice' => $invoice,
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'address_line', 'postal_code', 'city', 'country', 'vat_number', 'kvk_number', 'email', 'payment_terms']),
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'description', 'unit', 'price', 'vat_rate']),
            'vat_rates' => VatCalculator::availableRates(),
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status !== 'draft') {
            return back()->withErrors(['status' => 'Verstuurde facturen kunnen niet worden gewijzigd.']);
        }

        $data = $this->validated($request);
        $this->manager->update($invoice, $data);

        if ($request->input('action') === 'send') {
            $this->manager->send($invoice);
            return redirect()->route('invoices.show', $invoice)->with('flash', "Factuur {$invoice->number} verstuurd.");
        }

        return redirect()->route('invoices.show', $invoice)->with('flash', 'Concept bijgewerkt.');
    }

    public function send(Invoice $invoice): RedirectResponse
    {
        $this->manager->send($invoice);
        return back()->with('flash', "Factuur {$invoice->number} verstuurd.");
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        if ($invoice->status !== 'draft') {
            return back()->withErrors(['delete' => 'Alleen concepten kunnen worden verwijderd.']);
        }
        $invoice->delete();
        return redirect()->route('invoices.index')->with('flash', 'Concept verwijderd.');
    }

    public function pdf(Invoice $invoice): HttpResponse
    {
        $invoice->load('lines');
        $company = auth()->user()->company;

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $company,
        ])->setPaper('a4');

        $filename = ($invoice->number ?: "concept-{$invoice->id}") . '.pdf';
        return $pdf->stream($filename);
    }

    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . ($invoice->remaining_amount + 0.01)],
            'paid_on' => ['required', 'date'],
            'method' => ['required', 'in:bank_transfer,ideal,cash,card,other'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        Payment::create(array_merge($data, ['invoice_id' => $invoice->id]));
        return back()->with('flash', 'Betaling geregistreerd.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'payment_terms' => ['required', 'integer', 'min:0', 'max:365'],
            'reference' => ['nullable', 'string', 'max:255'],
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
