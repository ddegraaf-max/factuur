<?php

/*
 * Markten: alles wat per land verschilt — taal, valuta, btw-tarieven,
 * identificatienummers, rechtsvormen, betaalmethoden, e-facturatie en de
 * incassopartner. Een merk (config/brand.php) verwijst met 'market' naar
 * één van deze sleutels. Gebruik App\Support\Market, market('…') of
 * $page.props.market in Vue.
 */
return [

    // Omgevingsoverschrijvingen — env() mag alleen hier (config wordt in productie gecachet).
    // Gelezen door App\Support\Market::incasso()/wykup(), WindykacjaService en NbpService.
    'overrides' => [
        'incasso' => [
            'partner_name' => env('INCASSO_PARTNER_NAME'),
            'claims_email' => env('INCASSO_CLAIMS_EMAIL'),
            'cc' => env('INCASSO_CC'),
        ],
        'wykup' => [
            'partner_name' => env('WYKUP_PARTNER_NAME'),
            'email' => env('WYKUP_EMAIL') ?: env('INCASSO_CLAIMS_EMAIL'),
            'cc' => env('WYKUP_CC'),
        ],
        'interest_rate' => env('WINDYKACJA_INTEREST_RATE'),
        'eur_pln' => env('WINDYKACJA_EUR_PLN'),
    ],

    'nl' => [
        'locale' => 'nl',
        'locales' => ['nl'],
        'country' => 'NL',
        'country_name' => 'Nederland',
        'phone_prefix' => '+31',
        'currency' => 'EUR',
        'currency_symbol' => '€',
        'symbol_position' => 'before',
        'thousands_separator' => '.',
        'decimal_separator' => ',',
        'date_format' => 'd-m-Y',

        'vat_rates' => [21, 9, 0],
        'default_vat' => 21,
        'vat_return' => 'nl',              // rubrieken van de Belastingdienst
        'tax_authority' => 'Belastingdienst',
        'e_invoicing' => 'peppol',

        // Identificatie van een onderneming.
        'tax_id' => ['label' => 'Btw-nummer', 'short' => 'BTW', 'placeholder' => 'NL123456789B01', 'regex' => '/^NL\d{9}B\d{2}$/i', 'required' => false, 'maxlength' => 14],
        'registry' => ['label' => 'KvK-nummer', 'short' => 'KVK', 'placeholder' => '12345678', 'digits' => [8, 8], 'required' => true],
        'company_types' => [
            'eenmanszaak' => 'ZZP / Eenmanszaak',
            'bv' => 'B.V.',
            'vof' => 'V.O.F.',
            'maatschap' => 'Maatschap',
            'stichting' => 'Stichting',
            'vereniging' => 'Vereniging',
            'other' => 'Anders',
        ],

        'payment' => ['online_label' => 'iDEAL', 'mollie_methods' => ['ideal']],
        'km_rate' => 0.23,
        'invoice_footer' => 'Bedankt voor uw vertrouwen! Gelieve het factuurbedrag binnen de betaaltermijn te voldoen onder vermelding van het factuurnummer.',

        'incasso' => [
            'partner_name' => 'Armaere Gerechtsdeurwaarders',
            'claims_email' => 'j.backers@armaere.nl',
            'cc' => 'info@creditline.nl',
        ],
    ],

    'pl' => [
        'locale' => 'pl',
        // Interfacetalen: Pools standaard, Engels voor buitenlandse ondernemers in Polen (PL/EN-schakelaar).
        'locales' => ['pl', 'en'],
        'country' => 'PL',
        'country_name' => 'Polska',
        'phone_prefix' => '+48',
        'currency' => 'PLN',
        'currency_symbol' => 'zł',
        'symbol_position' => 'after',
        'thousands_separator' => "\u{00A0}", // vaste spatie: "1 234,50 zł" breekt nooit af
        'decimal_separator' => ',',
        'date_format' => 'd.m.Y',

        'vat_rates' => [23, 8, 5, 0],
        'default_vat' => 23,
        'vat_return' => 'pl',              // JPK_V7: VAT należny/naliczony per stawka
        'tax_authority' => 'Urząd Skarbowy',
        'e_invoicing' => 'ksef',           // Krajowy System e-Faktur (FA-XML)

        'tax_id' => ['label' => 'NIP', 'short' => 'NIP', 'placeholder' => '1234567890', 'regex' => '/^(PL)?\d{10}$/', 'required' => true, 'maxlength' => 13],
        'registry' => ['label' => 'REGON', 'short' => 'REGON', 'placeholder' => '123456789', 'digits' => [9, 14], 'required' => false],
        'company_types' => [
            'jdg' => 'Jednoosobowa działalność gospodarcza',
            'sp_zoo' => 'Spółka z o.o.',
            'sp_j' => 'Spółka jawna',
            'sp_k' => 'Spółka komandytowa',
            'sa' => 'Spółka akcyjna',
            'sp_cywilna' => 'Spółka cywilna',
            'fundacja' => 'Fundacja / stowarzyszenie',
            'other' => 'Inna',
        ],

        'payment' => ['online_label' => 'BLIK / Przelewy24', 'mollie_methods' => ['blik', 'przelewy24', 'creditcard']],
        'km_rate' => 1.15,
        'invoice_footer' => 'Dziękujemy za zaufanie! Prosimy o zapłatę w terminie, podając numer faktury w tytule przelewu.',

        // Windykacja: odsetki ustawowe za opóźnienie w transakcjach handlowych
        // (stopa referencyjna NBP + 10 p.p.) en de vaste rekompensata (art. 10
        // ustawy z 8 marca 2013 r.) van 40/70/100 EUR, omgerekend naar PLN.
        'interest_rate' => 0.14, // vangnet als de tabel hieronder leeg is
        // Odsetki ustawowe za opóźnienie w transakcjach handlowych (dłużnik niebędący podmiotem
        // leczniczym): NBP-referentierente op 1 januari / 1 juli + 10 p.p., elk halfjaar in het
        // Monitor Polski. ELK HALFJAAR AANVULLEN (obwieszczenie ± 20 juni / 11 december).
        // 2025-H2 … 2026-H2 uit de obwieszczenia (M.P. 2025 poz. 602, 2025 poz. 1257, 2026 poz. 642);
        // eerdere jaren afgeleid uit de referentierente van dat moment.
        'interest_rates' => [
            '2023-01-01' => 0.1675,
            '2023-07-01' => 0.1675,
            '2024-01-01' => 0.1575,
            '2024-07-01' => 0.1575,
            '2025-01-01' => 0.1575,
            '2025-07-01' => 0.1525,
            '2026-01-01' => 0.14,
            '2026-07-01' => 0.1375,
        ],
        'eur_pln' => 4.30,
        // Geen incassopartner in Polen: herinneringen en het wezwanie do zapłaty verstuurt
        // de ondernemer zelf vanuit Lopra. sprzedamfakture.pl koopt onbetaalde facturen
        // (wykup wierzytelności) — zie 'wykup'. Per omgeving: WYKUP_EMAIL (of INCASSO_CLAIMS_EMAIL).
        'incasso' => null,
        'wykup' => [
            'partner_name' => 'sprzedamfakture.pl',
            'email' => 'wykup@sprzedamfakture.pl',
            'cc' => 'info@creditline.nl',
            'website' => 'https://sprzedamfakture.pl',
        ],
    ],
];
