<?php

// Kleine globale helpers. Wordt geladen vanuit AppServiceProvider::register()
// (geen composer dump-autoload nodig).

if (! function_exists('brand')) {
    /**
     * Merkgegeven van het actieve merk (EasyInvoice, Lopra, …), zie config/brand.php.
     * brand() geeft de naam; brand('email'), brand('mark') … een specifiek gegeven.
     */
    function brand(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? \App\Support\Brand::name() : \App\Support\Brand::get($key, $default);
    }
}

if (! function_exists('market')) {
    /**
     * Marktgegeven (land) van deze omgeving, zie config/markets.php.
     * market() geeft de sleutel ('nl'/'pl'); market('currency'), market('tax_id.label') … een gegeven.
     */
    function market(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? \App\Support\Market::key() : \App\Support\Market::get($key, $default);
    }
}

if (! function_exists('money')) {
    /** Geldbedrag in de schrijfwijze van de markt: "€ 1.234,50" (nl) of "1 234,50 zł" (pl). */
    function money(float|int|string|null $amount, bool $withSymbol = true, int $decimals = 2): string
    {
        return \App\Support\Market::money($amount, $withSymbol, $decimals);
    }
}
