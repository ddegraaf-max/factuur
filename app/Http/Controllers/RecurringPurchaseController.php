<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\RecurringPurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vaste lasten (terugkerende inkoop): profielen beheren die periodiek
 * automatisch een inkoopfactuur inboeken. Het inboeken zelf gebeurt
 * dagelijks via purchases:generate-recurring.
 */
class RecurringPurchaseController extends Controller
{
    public function index(): Response
    {
        $profiles = RecurringPurchase::orderBy('supplier_name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'supplier_name' => $p->supplier_name,
                'category' => $p->category,
                'frequency' => $p->frequency,
                'frequency_label' => $p->frequency_label,
                'next_run_on' => $p->next_run_on->format('Y-m-d'),
                'next_run_label' => $p->next_run_on->translatedFormat('j M Y'),
                'end_date' => $p->end_date?->format('Y-m-d'),
                'end_date_label' => $p->end_date?->translatedFormat('j M Y'),
                'last_run_label' => $p->last_run_on?->translatedFormat('j M Y'),
                'active' => $p->active,
                'vat_lines' => $p->vat_lines,
                'total' => $p->totalAmount(),
                'auto_paid' => $p->auto_paid,
                'payment_method' => $p->payment_method,
                'notes' => $p->notes,
                'purchases_generated' => $p->purchases_generated,
            ]);

        return Inertia::render('Inkoop/Terugkerend', [
            'profiles' => $profiles,
            'suppliers' => PurchaseInvoice::selectRaw('supplier_name, MAX(id) AS last_id')
                ->groupBy('supplier_name')->orderByDesc('last_id')->limit(100)->pluck('supplier_name'),
            'categories' => PurchaseInvoiceController::categories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['start_date'] = $data['next_run_on'];

        RecurringPurchase::create($data);

        return back()->with('flash', __('Vaste last aangemaakt — de eerste inboeking volgt op de gekozen datum.'));
    }

    public function update(Request $request, RecurringPurchase $profile): RedirectResponse
    {
        // Alleen actief/inactief wisselen (pauzeren) kan zonder het hele formulier.
        if ($request->has('active') && count($request->all()) === 1) {
            $profile->update(['active' => $request->boolean('active')]);

            return back()->with('flash', $profile->active ? __('Vaste last hervat.') : __('Vaste last gepauzeerd.'));
        }

        $profile->update($this->validated($request));

        return back()->with('flash', __('Vaste last bijgewerkt.'));
    }

    public function destroy(RecurringPurchase $profile): RedirectResponse
    {
        // Al ingeboekte inkoopfacturen blijven gewoon staan.
        $profile->delete();

        return back()->with('flash', __('Vaste last verwijderd — al ingeboekte inkoopfacturen blijven bewaard.'));
    }

    /** Snelstart: maak van een bestaande inkoopfactuur een maandelijkse vaste last. */
    public function createFromPurchase(PurchaseInvoice $purchase): RedirectResponse
    {
        $profile = RecurringPurchase::create([
            'supplier_name' => $purchase->supplier_name,
            'category' => $purchase->category,
            'frequency' => 'monthly',
            'start_date' => $purchase->invoice_date->toDateString(),
            'next_run_on' => $purchase->invoice_date->copy()->addMonthNoOverflow()->toDateString(),
            'vat_lines' => $purchase->vat_lines,
            'auto_paid' => $purchase->status === 'paid',
            'payment_method' => $purchase->payment_method ?: 'direct_debit',
            'notes' => $purchase->notes,
        ]);

        return redirect()->route('purchases.recurring.index')
            ->with('flash', __('":supplier" staat nu als maandelijkse vaste last — pas zo nodig de frequentie aan.', ['supplier' => $profile->supplier_name]));
    }

    /* ===================== Helpers ===================== */

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'supplier_name' => ['required', 'string', 'max:180'],
            'category' => ['nullable', 'string', 'max:60'],
            'frequency' => ['required', Rule::in(array_keys(RecurringPurchase::FREQUENCIES))],
            'next_run_on' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:next_run_on'],
            'active' => ['nullable', 'boolean'],
            'vat_lines' => ['required', 'array', 'min:1'],
            'vat_lines.*.base' => ['required', 'numeric', 'between:-9999999,9999999'],
            'vat_lines.*.rate' => ['required', 'numeric', 'in:' . implode(',', \App\Support\Market::vatRates())],
            'vat_lines.*.vat' => ['required', 'numeric', 'between:-9999999,9999999'],
            'auto_paid' => ['nullable', 'boolean'],
            'payment_method' => ['nullable', 'in:bank_transfer,ideal,cash,card,direct_debit,other'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'supplier_name.required' => __('Vul de naam van de leverancier in.'),
            'next_run_on.required' => __('Kies de datum van de (eerst)volgende inboeking.'),
            'vat_lines.required' => __('Voeg minstens één bedragregel toe.'),
            'end_date.after' => __('De einddatum moet ná de volgende inboeking liggen.'),
        ]);

        return [
            'supplier_name' => trim($data['supplier_name']),
            'category' => $data['category'] ?? null,
            'frequency' => $data['frequency'],
            'next_run_on' => $data['next_run_on'],
            'end_date' => $data['end_date'] ?? null,
            'active' => (bool) ($data['active'] ?? true),
            'vat_lines' => collect($data['vat_lines'])->map(fn ($l) => [
                'base' => round((float) $l['base'], 2),
                'rate' => (float) $l['rate'],
                'vat' => round((float) $l['vat'], 2),
            ])->values()->all(),
            'auto_paid' => (bool) ($data['auto_paid'] ?? true),
            'payment_method' => $data['payment_method'] ?? 'direct_debit',
            'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
        ];
    }
}
