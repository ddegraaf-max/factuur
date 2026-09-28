<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;

/**
 * De startlijst op het dashboard: de drie stappen van een nieuw account naar
 * de eerste verstuurde factuur of offerte. Het registratieformulier vraagt
 * alleen naam, bedrijfsnaam, e-mailadres en wachtwoord; wat er op een factuur
 * hoort te staan (adres, KvK-nummer, IBAN) komt hier aan bod.
 *
 * De lijst verdwijnt zodra de bedrijfsgegevens compleet zijn en er iets is
 * verstuurd.
 */
class Onboarding
{
    /**
     * @return array{done: int, total: int, steps: list<array{key: string, done: bool, title: string, text: string, route: string}>}|null
     */
    public static function for(?Company $company): ?array
    {
        if (! $company || $company->is_demo) {
            return null;
        }

        $sent = Invoice::where('company_id', $company->id)->where('status', '!=', 'draft')->exists()
            || Quote::where('company_id', $company->id)->where('status', '!=', 'draft')->exists();
        $details = self::detailsComplete($company);

        if ($sent && $details) {
            return null;
        }

        $steps = [
            [
                'key' => 'company',
                'done' => $details,
                'title' => __('Vul je bedrijfsgegevens in'),
                'text' => Market::isPl()
                    ? __('Je adres en rekeningnummer komen op elke factuur.')
                    : __('Je adres, KvK-nummer en IBAN komen op elke factuur.'),
                'route' => route('settings.company'),
            ],
            [
                'key' => 'customer',
                'done' => $sent || Customer::where('company_id', $company->id)->exists(),
                'title' => __('Voeg je eerste klant toe'),
                'text' => __('Naam en e-mailadres zijn genoeg om te beginnen.'),
                'route' => route('customers.create'),
            ],
            [
                'key' => 'send',
                'done' => $sent,
                'title' => __('Verstuur je eerste factuur of offerte'),
                'text' => __('Je klant krijgt een mail met de PDF en een link om online te bekijken.'),
                'route' => route('invoices.create'),
            ],
        ];

        return [
            'done' => count(array_filter($steps, fn ($step) => $step['done'])),
            'total' => count($steps),
            'steps' => $steps,
        ];
    }

    /** Staat alles erop wat een factuur nodig heeft? */
    public static function detailsComplete(Company $company): bool
    {
        $needed = ['address_line', 'postal_code', 'city', 'iban'];
        if (! Market::isPl()) {
            $needed[] = 'kvk_number';
        }

        foreach ($needed as $field) {
            if (blank($company->{$field})) {
                return false;
            }
        }

        return true;
    }
}
