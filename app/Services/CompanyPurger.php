<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Verwijdert een complete administratie: alle documenten, klanten,
 * instellingen én de gebruikers die er (alleen) bij horen.
 *
 * Bijna alle tabellen hangen met cascadeOnDelete aan companies; de
 * uitzonderingen worden hier expliciet in de juiste volgorde opgeruimd
 * (facturen en offertes verwijzen met restrict naar klanten, creditnota's
 * naar hun oorspronkelijke factuur, herinneringslogs hebben geen constraint).
 */
class CompanyPurger
{
    public function purge(Company $company): void
    {
        DB::transaction(function () use ($company) {
            $id = $company->id;

            $invoiceIds = DB::table('invoices')->where('company_id', $id)->pluck('id');
            if ($invoiceIds->isNotEmpty()) {
                foreach (['reminder_logs', 'payments', 'online_payments', 'invoice_views', 'invoice_lines'] as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->whereIn('invoice_id', $invoiceIds)->delete();
                    }
                }
                DB::table('attachments')->where('company_id', $id)->delete();
                DB::table('invoices')->where('company_id', $id)->where('is_credit', true)->delete();
                DB::table('invoices')->where('company_id', $id)->delete();
            }

            $quoteIds = DB::table('quotes')->where('company_id', $id)->pluck('id');
            if ($quoteIds->isNotEmpty()) {
                foreach (['quote_installments', 'quote_lines'] as $table) {
                    DB::table($table)->whereIn('quote_id', $quoteIds)->delete();
                }
                DB::table('quotes')->where('company_id', $id)->delete();
            }

            // Alle overige tabellen met een company_id — onbekende/nieuwe tabellen
            // worden zo automatisch meegenomen. Twee rondes: een tabel die nog
            // ergens naar verwijst (restrict) slaagt in de tweede ronde alsnog.
            $tables = collect(Schema::getTableListing())
                ->reject(fn ($t) => in_array($t, ['companies', 'users', 'invoices', 'quotes', 'migrations'], true))
                ->filter(fn ($t) => Schema::hasColumn($t, 'company_id'))
                ->values();
            for ($round = 0; $round < 2; $round++) {
                foreach ($tables as $table) {
                    try {
                        DB::transaction(fn () => DB::table($table)->where('company_id', $id)->delete());
                    } catch (\Throwable $e) {
                        if ($round === 1) {
                            throw $e;
                        }
                    }
                }
            }

            // Gebruikers die alléén deze administratie hadden gaan mee; wie ook
            // elders lid is, houdt zijn account en verliest alleen deze koppeling.
            $userIds = DB::table('users')->where('company_id', $id)->pluck('id');
            foreach ($userIds as $userId) {
                $elsewhere = Schema::hasTable('company_user')
                    ? DB::table('company_user')->where('user_id', $userId)->where('company_id', '!=', $id)->value('company_id')
                    : null;
                if ($elsewhere) {
                    DB::table('users')->where('id', $userId)->update(['company_id' => $elsewhere]);
                } else {
                    DB::table('users')->where('id', $userId)->delete();
                }
            }

            DB::table('companies')->where('id', $id)->delete();
        });
    }
}
