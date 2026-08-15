<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\RecurringInvoice;
use App\Services\VatCalculator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RecurringInvoiceController extends Controller
{
    public function __construct(protected VatCalculator $vat) {}

    public function index(): Response
    {
        $profiles = RecurringInvoice::with('customer:id,name')
            ->orderByDesc('active')
            ->orderBy('next_run_on')
            ->get()
            ->map(function (RecurringInvoice $r) {
                $totals = $this->vat->calculateInvoice($r->lines ?? []);

                return [
                    'id' => $r->id,
                    'customer_name' => $r->customer?->name ?? '—',
                    'description' => $r->reference ?: ($r->lines[0]['description'] ?? '—'),
                    'frequency' => $r->frequency,
                    'frequency_label' => $r->frequency_label,
                    'next_run_on' => $r->next_run_on->format('Y-m-d'),
                    'next_run_label' => $r->next_run_on->translatedFormat('j M Y'),
                    'end_date' => $r->end_date?->format('Y-m-d'),
                    'last_run_label' => $r->last_run_on?->translatedFormat('j M Y'),
                    'invoices_generated' => $r->invoices_generated,
                    'auto_send' => $r->auto_send,
                    'active' => $r->active,
                    'total' => $totals['total'],
                    'lines_count' => count($r->lines ?? []),
                ];
            });

        return Inertia::render('Recurring/Index', [
            'profiles' => $profiles,
            'frequencies' => RecurringInvoice::FREQUENCIES,
        ]);
    }

    /**
     * Maak een terugkerend profiel op basis van een bestaande factuur.
     */
    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->is_credit) {
            return back()->withErrors(['recurring' => 'Een creditnota kan niet terugkerend worden gemaakt.']);
        }

        $data = $request->validate([
            'frequency' => ['required', Rule::in(array_keys(RecurringInvoice::FREQUENCIES))],
            'next_run_on' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:next_run_on'],
            'auto_send' => ['required', 'boolean'],
        ]);

        $invoice->load('lines');

        RecurringInvoice::create([
            'customer_id' => $invoice->customer_id,
            'source_invoice_id' => $invoice->id,
            'frequency' => $data['frequency'],
            'start_date' => $data['next_run_on'],
            'next_run_on' => $data['next_run_on'],
            'end_date' => $data['end_date'] ?? null,
            'auto_send' => $data['auto_send'],
            'active' => true,
            'reference' => $invoice->reference,
            'notes' => $invoice->notes,
            'payment_terms' => $invoice->payment_terms ?? 30,
            'lines' => $invoice->lines->map(fn ($l) => [
                'product_id' => $l->product_id,
                'description' => $l->description,
                'details' => $l->details,
                'quantity' => (float) $l->quantity,
                'unit' => $l->unit,
                'unit_price' => (float) $l->unit_price,
                'vat_rate' => (float) $l->vat_rate,
            ])->values()->all(),
        ]);

        return redirect()->route('recurring.index')
            ->with('flash', 'Terugkerend profiel aangemaakt. De eerste factuur volgt op '.Carbon::parse($data['next_run_on'])->translatedFormat('j M Y').'.');
    }

    public function update(Request $request, RecurringInvoice $recurring): RedirectResponse
    {
        $data = $request->validate([
            'frequency' => ['sometimes', Rule::in(array_keys(RecurringInvoice::FREQUENCIES))],
            'next_run_on' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'auto_send' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
        ]);

        // Wie de eerstvolgende datum wijzigt, verlegt ook het maand-anker.
        if (isset($data['next_run_on'])) {
            $data['start_date'] = $data['next_run_on'];
        }

        $recurring->update($data);

        return back()->with('flash', 'Terugkerend profiel bijgewerkt.');
    }

    public function destroy(RecurringInvoice $recurring): RedirectResponse
    {
        $recurring->delete();

        return back()->with('flash', 'Terugkerend profiel verwijderd. Al gegenereerde facturen blijven bestaan.');
    }
}
