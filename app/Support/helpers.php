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
