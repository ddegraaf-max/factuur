<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\RecurringPurchase;
use Illuminate\Support\Facades\Log;

/**
 * Boekt inkoopfacturen in uit terugkerende-inkoopprofielen ("vaste lasten").
 * Draait dagelijks via `purchases:generate-recurring` (zie routes/console.php).
 *
 * Zelfde semantiek als de terugkerende verkoopfacturen: per run maximaal één
 * inkoopfactuur per profiel; gemiste periodes worden dag-voor-dag ingehaald
 * (next_run_on schuift telkens één frequentiestap op).
 */
class RecurringPurchaseService
{
    /** Boek alle profielen in die aan de beurt zijn; geeft het aantal terug. */
    public function runDue(): int
    {
        $due = RecurringPurchase::withoutGlobalScope('company')
            ->where('active', true)
            ->whereDate('next_run_on', '<=', today())
            ->get();

        $count = 0;
        foreach ($due as $profile) {
            try {
                if ($this->generate($profile)) {
                    $count++;
                }
            } catch (\Throwable $e) {
                Log::error('Terugkerende inkoop inboeken mislukt', [
                    'recurring_purchase' => $profile->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /** Boek één inkoopfactuur in voor dit profiel en schuif de planning op. */
    public function generate(RecurringPurchase $profile): ?PurchaseInvoice
    {
        // Einddatum gepasseerd → profiel stopzetten zonder inboeking.
        if ($profile->end_date && $profile->next_run_on->gt($profile->end_date)) {
            $profile->update(['active' => false]);

            return null;
        }

        $lines = [];
        $subtotal = 0.0;
        $vatTotal = 0.0;
        foreach ($profile->vat_lines ?? [] as $line) {
            $base = round((float) ($line['base'] ?? 0), 2);
            $vat = round((float) ($line['vat'] ?? 0), 2);
            $lines[] = ['base' => $base, 'rate' => (float) ($line['rate'] ?? \App\Support\Market::defaultVatRate()), 'vat' => $vat];
            $subtotal += $base;
            $vatTotal += $vat;
        }

        $purchase = PurchaseInvoice::create([
            // Expliciet: dit draait via de console, zonder ingelogde gebruiker.
            'company_id' => $profile->company_id,
            'supplier_name' => $profile->supplier_name,
            'category' => $profile->category,
            'invoice_date' => $profile->next_run_on->toDateString(),
            'due_date' => null,
            // Vaste lasten via incasso staan meteen op betaald.
            'status' => $profile->auto_paid ? 'paid' : 'open',
            'paid_at' => $profile->auto_paid ? $profile->next_run_on->toDateString() : null,
            'payment_method' => $profile->auto_paid ? ($profile->payment_method ?: 'direct_debit') : null,
            'subtotal' => round($subtotal, 2),
            'vat_total' => round($vatTotal, 2),
            'total' => round($subtotal + $vatTotal, 2),
            'vat_lines' => $lines,
            'notes' => trim(($profile->notes ? $profile->notes . ' — ' : '') . __('Automatisch ingeboekt (vaste lasten).')),
        ]);

        $next = $profile->nextDateAfter($profile->next_run_on);

        $profile->forceFill([
            'last_run_on' => $profile->next_run_on,
            'next_run_on' => $next,
            'purchases_generated' => $profile->purchases_generated + 1,
            'active' => ($profile->end_date && $next->gt($profile->end_date)) ? false : $profile->active,
        ])->save();

        return $purchase;
    }
}
