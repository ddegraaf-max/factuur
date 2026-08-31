<?php

namespace App\Support;

/**
 * De markt (het land) waarin deze omgeving draait — zie config/markets.php.
 * Het merk (config/brand.php) bepaalt de markt: EasyInvoice en Lopra draaien
 * in 'nl', Lopra Polska in 'pl'. Alles wat per land verschilt (taal, valuta,
 * btw-tarieven, NIP/KvK, betaalmethoden, incassopartner) komt hier vandaan.
 */
class Market
{
    public const DEFAULT = 'nl';

    public static function key(): string
    {
        $key = (string) Brand::get('market', self::DEFAULT);

        return isset(config('markets', [])[$key]) ? $key : self::DEFAULT;
    }

    public static function is(string $key): bool
    {
        return self::key() === $key;
    }

    public static function isPl(): bool
    {
        return self::is('pl');
    }

    /** Eén marktgegeven (dot-notatie toegestaan: 'tax_id.label'). */
    public static function get(string $key, mixed $default = null): mixed
    {
        return config('markets.' . self::key() . '.' . $key, $default);
    }

    public static function locale(): string
    {
        return (string) self::get('locale', 'nl');
    }

    /**
     * Interfacetalen waaruit de gebruiker mag kiezen ('locales' in config/markets.php);
     * de markttaal staat altijd vooraan. Nederland: alleen nl; Polen: pl en en.
     *
     * @return string[]
     */
    public static function uiLocales(): array
    {
        return array_values(array_unique(array_merge([self::locale()], (array) self::get('locales', []))));
    }

    public static function isUiLocale(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::uiLocales(), true);
    }

    /**
     * Vertaalde tegenhanger van een Blade-view voor de actieve interfacetaal, als die
     * bestaat: 'lopra-pl.landing' wordt 'lopra-pl.en.landing' zodra de bezoeker EN koos.
     * Anders gewoon de view zelf.
     */
    public static function view(string $name): string
    {
        $locale = app()->getLocale();
        if ($locale === self::locale()) {
            return $name;
        }

        $parts = explode('.', $name);
        $leaf = array_pop($parts);
        $candidate = implode('.', array_merge($parts, [$locale, $leaf]));

        return view()->exists($candidate) ? $candidate : $name;
    }

    public static function country(): string
    {
        return (string) self::get('country', 'NL');
    }

    public static function currency(): string
    {
        return (string) self::get('currency', 'EUR');
    }

    public static function symbol(): string
    {
        return (string) self::get('currency_symbol', '€');
    }

    /**
     * Geldbedrag in de schrijfwijze van de markt: nl "€ 1.234,50", pl "1 234,50 zł".
     * Negatieve bedragen krijgen het minteken vóór het symbool: "-€ 12,00" / "-12,00 zł".
     */
    public static function money(float|int|string|null $amount, bool $withSymbol = true, int $decimals = 2): string
    {
        $amount = (float) ($amount ?? 0);
        $number = number_format(abs($amount), $decimals, (string) self::get('decimal_separator', ','), (string) self::get('thousands_separator', '.'));
        $sign = $amount < 0 ? '-' : '';

        if (! $withSymbol) {
            return $sign . $number;
        }

        return self::get('symbol_position', 'before') === 'after'
            ? $sign . $number . "\u{00A0}" . self::symbol()
            : $sign . self::symbol() . "\u{00A0}" . $number;
    }

    /** @return int[] bijv. [21, 9, 0] of [23, 8, 5, 0] */
    public static function vatRates(): array
    {
        return array_map('intval', (array) self::get('vat_rates', [21, 9, 0]));
    }

    /** Opties voor keuzelijsten: [['value' => 23, 'label' => '23%'], …]. */
    public static function vatRateOptions(): array
    {
        return array_map(fn (int $rate) => ['value' => $rate, 'label' => $rate . '%'], self::vatRates());
    }

    public static function defaultVatRate(): int
    {
        return (int) self::get('default_vat', 21);
    }

    /** Dichtstbijzijnde geldige btw-tarief (voor imports en AI-herkenning). */
    public static function nearestVatRate(float|int|null $rate): int
    {
        $rates = self::vatRates();
        if ($rate === null) {
            return self::defaultVatRate();
        }
        usort($rates, fn ($a, $b) => abs($a - $rate) <=> abs($b - $rate));

        return (int) $rates[0];
    }

    /** @return array<string, string> rechtsvormen: sleutel => label */
    public static function companyTypes(): array
    {
        return (array) self::get('company_types', []);
    }

    /** Incassopartner van deze markt; per omgeving te overschrijven met INCASSO_PARTNER_NAME / INCASSO_CLAIMS_EMAIL / INCASSO_CC. */
    public static function incasso(string $key): string
    {
        $env = config('markets.overrides.incasso.' . $key);

        return (string) ($env ?: self::get('incasso.' . $key, ''));
    }

    /** Heeft deze markt een incassopartner (dossier overdragen)? Polen niet: daar verkoop je de factuur. */
    public static function hasIncasso(): bool
    {
        return self::incasso('partner_name') !== '';
    }

    /**
     * Factuurkoper van deze markt (Polen: sprzedamfakture.pl — wykup wierzytelności, geen incasso).
     * Per omgeving te overschrijven met WYKUP_PARTNER_NAME / WYKUP_EMAIL (of INCASSO_CLAIMS_EMAIL) / WYKUP_CC.
     */
    public static function wykup(string $key): string
    {
        $env = config('markets.overrides.wykup.' . $key);

        return (string) ($env ?: self::get('wykup.' . $key, ''));
    }

    public static function hasWykup(): bool
    {
        return self::wykup('partner_name') !== '';
    }

    /** Wat de Vue-kant nodig heeft (gedeeld via Inertia als $page.props.market). */
    public static function forClient(): array
    {
        return [
            'key' => self::key(),
            'locale' => self::locale(),
            'locales' => self::uiLocales(),
            'country' => self::country(),
            'country_name' => (string) self::get('country_name', ''),
            'currency' => self::currency(),
            'symbol' => self::symbol(),
            'symbol_position' => (string) self::get('symbol_position', 'before'),
            'vat_rates' => self::vatRates(),
            'default_vat' => self::defaultVatRate(),
            'vat_return' => (string) self::get('vat_return', 'nl'),
            'e_invoicing' => (string) self::get('e_invoicing', 'peppol'),
            'tax_authority' => (string) self::get('tax_authority', ''),
            'tax_id' => ['label' => (string) self::get('tax_id.label'), 'short' => (string) self::get('tax_id.short'), 'placeholder' => (string) self::get('tax_id.placeholder'), 'required' => (bool) self::get('tax_id.required'), 'maxlength' => (int) self::get('tax_id.maxlength', 20)],
            'registry' => ['label' => (string) self::get('registry.label'), 'short' => (string) self::get('registry.short'), 'placeholder' => (string) self::get('registry.placeholder'), 'required' => (bool) self::get('registry.required'), 'digits' => (array) self::get('registry.digits', [8, 8])],
            'company_types' => self::companyTypes(),
            'online_payment_label' => (string) self::get('payment.online_label', 'iDEAL'),
            'km_rate' => (float) self::get('km_rate', 0.23),
            'incasso_partner' => self::incasso('partner_name'),
            'wykup_partner' => self::wykup('partner_name'),
            'wykup_website' => self::wykup('website'),
            'interest_rate' => (float) self::get('interest_rate', 0),
        ];
    }
}
