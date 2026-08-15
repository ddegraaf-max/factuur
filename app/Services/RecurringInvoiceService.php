<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\RecurringInvoice;
use Illuminate\Support\Facades\Log;

/**
 * RecurringInvoiceService
 *
 * Genereert facturen uit terugkerende profielen. Draait dagelijks via
 * `invoices:generate-recurring` (zie routes/console.php).
 *
 * Per run wordt per profiel maximaal één factuur gegenereerd; liep de app
 * een tijd niet, dan haalt het schema de gemiste periodes dag-voor-dag in
 * (next_run_on schuift telkens één frequentiestap op).
 */
class RecurringInvoiceService
{
    public function __construct(protected InvoiceManager $manager) {}

    /**
     * Genereer facturen voor alle profielen die aan de beurt zijn.
     * Geeft het aantal gegenereerde facturen terug.
     */
    public function runDue(): int
    {
        // Demo-omgevingen slaan we over: die mogen niets naar buiten sturen.
        $due = RecurringInvoice::query()
            ->where('active', true)
            ->whereDate('next_run_on', '<=', today())
            ->whereHas('company', fn ($q) => $q->where('is_demo', false))
            ->with('customer')
            ->get();

        $count = 0;
        foreach ($due as $profile) {
            try {
                if ($this->generate($profile)) {
                    $count++;
                }
            } catch (\Throwable $e) {
                Log::error('Terugkerende factuur genereren mislukt', [
                    'recurring_invoice' => $profile->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Genereer één factuur voor dit profiel en schuif de planning op.
     */
    public function generate(RecurringInvoice $profile): ?Invoice
    {
        // Einddatum gepasseerd → profiel stopzetten zonder factuur.
        if ($profile->end_date && $profile->next_run_on->gt($profile->end_date)) {
            $profile->update(['active' => false]);

            return null;
        }

        if (! $profile->customer) {
            Log::warning('Terugkerend profiel zonder klant overgeslagen', ['recurring_invoice' => $profile->id]);
            $profile->update(['active' => false]);

            return null;
        }

        $invoice = $this->manager->create([
            'company_id' => $profile->company_id,
            'customer_id' => $profile->customer_id,
            'invoice_date' => $profile->next_run_on->toDateString(),
            'payment_terms' => $profile->payment_terms,
            'reference' => $profile->reference,
            'notes' => $profile->notes,
            'lines' => $profile->lines ?? [],
        ]);

        if ($profile->auto_send) {
            $invoice = $this->manager->send($invoice);
        }

        $next = $profile->nextDateAfter($profile->next_run_on);

        $profile->forceFill([
            'last_run_on' => $profile->next_run_on,
            'next_run_on' => $next,
            'invoices_generated' => $profile->invoices_generated + 1,
            'active' => ($profile->end_date && $next->gt($profile->end_date)) ? false : $profile->active,
        ])->save();

        return $invoice;
    }
}
