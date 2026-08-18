<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 * Verwijdert een demo-omgeving met alles wat eraan hangt.
 *
 * De volgorde is belangrijk: facturen verwijzen met een restrict-constraint
 * naar klanten, en creditnota's naar hun oorspronkelijke factuur.
 */
class DemoCleaner
{
    public function delete(Company $company): void
    {
        if (! $company->is_demo) {
            throw new \DomainException('Alleen demo-omgevingen kunnen zo worden verwijderd.');
        }

        DB::transaction(function () use ($company) {
            $invoiceIds = DB::table('invoices')->where('company_id', $company->id)->pluck('id');

            if ($invoiceIds->isNotEmpty()) {
                DB::table('reminder_logs')->whereIn('invoice_id', $invoiceIds)->delete();
                DB::table('payments')->whereIn('invoice_id', $invoiceIds)->delete();
                DB::table('invoice_lines')->whereIn('invoice_id', $invoiceIds)->delete();
                DB::table('attachments')
                    ->where('attachable_type', \App\Models\Invoice::class)
                    ->whereIn('attachable_id', $invoiceIds)
                    ->delete();

                // Eerst de creditnota's: die verwijzen naar de facturen die ze crediteren.
                DB::table('invoices')->where('company_id', $company->id)->where('is_credit', true)->delete();
                DB::table('invoices')->where('company_id', $company->id)->delete();
            }

            $purchaseIds = DB::table('purchase_invoices')->where('company_id', $company->id)->pluck('id');
            if ($purchaseIds->isNotEmpty()) {
                DB::table('attachments')
                    ->where('attachable_type', \App\Models\PurchaseInvoice::class)
                    ->whereIn('attachable_id', $purchaseIds)
                    ->delete();
                DB::table('purchase_invoices')->where('company_id', $company->id)->delete();
            }

            $quoteIds = DB::table('quotes')->where('company_id', $company->id)->pluck('id');
            if ($quoteIds->isNotEmpty()) {
                DB::table('quote_lines')->whereIn('quote_id', $quoteIds)->delete();
                DB::table('quotes')->where('company_id', $company->id)->delete();
            }
            DB::table('quote_sequences')->where('company_id', $company->id)->delete();

            DB::table('recurring_invoices')->where('company_id', $company->id)->delete();
            DB::table('products')->where('company_id', $company->id)->delete();
            DB::table('customers')->where('company_id', $company->id)->delete();
            DB::table('invoice_sequences')->where('company_id', $company->id)->delete();
            DB::table('incasso_sequences')->where('company_id', $company->id)->delete();
            DB::table('users')->where('company_id', $company->id)->delete();

            $company->delete();
        });
    }

    /** Ruim alle verlopen demo-omgevingen op. Geeft het aantal verwijderde omgevingen terug. */
    public function purgeExpired(): int
    {
        $expired = Company::where('is_demo', true)
            ->whereNotNull('demo_expires_at')
            ->where('demo_expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expired as $company) {
            $this->delete($company);
            $count++;
        }

        return $count;
    }
}
