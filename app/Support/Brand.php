<?php

namespace App\Support;

/**
 * Het actieve merk (EasyInvoice, Lopra, …) — zie config/brand.php.
 *
 * Eén codebase, meerdere merken: elke omgeving draait met één APP_BRAND en
 * haalt naam, domein, logo's en teksten hier vandaan. In Blade is er de
 * helper brand('…'), in Vue $page.props.brand (zie HandleInertiaRequests).
 */
class Brand
{
    public const DEFAULT = 'easyinvoice';

    /** Sleutel van het actieve merk; valt terug op EasyInvoice bij een onbekende waarde. */
    public static function key(): string
    {
        $key = strtolower(trim((string) config('brand.active', self::DEFAULT)));

        return isset(config('brand.brands', [])[$key]) ? $key : self::DEFAULT;
    }

    public static function is(string $key): bool
    {
        return static::key() === strtolower($key);
    }

    /** Eén merkgegeven, met de per-omgeving-overrides (BRAND_EMAIL …) eroverheen. */
    public static function get(string $key, mixed $default = null): mixed
    {
        $override = config('brand.overrides.' . $key);
        if ($override !== null && $override !== '') {
            return $override;
        }

        return config('brand.brands.' . static::key() . '.' . $key, $default);
    }

    public static function name(): string
    {
        return (string) static::get('name', 'EasyInvoice');
    }

    public static function tagline(): string
    {
        return (string) static::get('tagline', '');
    }

    public static function email(): string
    {
        return (string) static::get('email', 'hallo@easyinvoice.nl');
    }

    /** Waar contactformulier en systeemmeldingen naartoe gaan (kan afwijken zolang een nieuwe mailbox nog niet bestaat). */
    public static function contactInbox(): string
    {
        return (string) (static::get('contact_inbox') ?: static::email());
    }

    public static function domain(): string
    {
        return (string) static::get('domain', 'easyinvoice.nl');
    }

    /** Absolute basis-URL van deze omgeving, zonder slash aan het eind. */
    public static function url(string $path = ''): string
    {
        return rtrim((string) config('app.url'), '/') . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }

    /** Absolute URL van een merkbestand (logo, favicon, OG-afbeelding). */
    public static function asset(string $key): string
    {
        $path = (string) static::get($key, '');

        return $path === '' ? '' : static::url($path);
    }

    /** Naam met ® als het merk geregistreerd is (voor juridische teksten). */
    public static function legalName(): string
    {
        return static::name() . (static::get('registered') ? '®' : '');
    }

    /** Versienummer in de huisstijl van het merk: 'Easy 1.44.0' wordt bij Lopra 'Lopra 1.44.0'. */
    public static function version(): string
    {
        return trim((string) static::get('version_prefix', static::name())) . ' ' . static::versionNumber();
    }

    public static function versionNumber(): string
    {
        return trim((string) preg_replace('/^[A-Za-z]+\s+/', '', (string) config('app.version', '1.0')));
    }

    /** Merkbewaking (verwarringspagina, dossier) hoort alleen bij het geregistreerde merk. */
    public static function watchesTrademark(): bool
    {
        return (bool) static::get('brand_watch', false);
    }

    /**
     * Plaatsvervangers in vaste teksten invullen: {brand}, {brand_email}, {brand_domain}.
     * Voor teksten die uit configuratie komen (help-artikelen) en dus geen Blade kunnen gebruiken.
     */
    public static function fill(?string $text): string
    {
        return str_replace(
            ['{brand}', '{brand_email}', '{brand_domain}'],
            [static::name(), static::email(), static::domain()],
            (string) $text,
        );
    }

    /** Wat de Vue-kant nodig heeft (gedeeld via Inertia als $page.props.brand). */
    public static function forClient(): array
    {
        return [
            'key' => static::key(),
            'name' => static::name(),
            'tagline' => static::tagline(),
            'positioning' => (string) static::get('positioning', ''),
            'email' => static::email(),
            'domain' => static::domain(),
            'url' => static::url(),
            'mark' => (string) static::get('mark', ''),
            'icon' => (string) static::get('icon', ''),
            'wordmark' => static::get('wordmark'),
            'wordmark_dark' => static::get('wordmark_dark'),
            'sidebar_mark' => static::get('sidebar_mark'),
            'dark_sidebar' => (bool) static::get('dark_sidebar', false),
            'color' => (string) static::get('color', '#E8231F'),
            'accent' => (string) static::get('accent', '#E8231F'),
            'auth_title' => (string) static::get('auth_title', ''),
            'auth_subtitle' => (string) static::get('auth_subtitle', ''),
            'registered' => (bool) static::get('registered', false),
        ];
    }

    /** Web-app-manifest (PWA) van het actieve merk. */
    public static function manifest(): array
    {
        $icons = [
            ['src' => static::get('apple_touch'), 'sizes' => '180x180', 'type' => 'image/png'],
            ['src' => static::get('favicon_512'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => static::get('icon'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ];

        return [
            'name' => static::name(),
            'short_name' => static::name(),
            'description' => (string) static::get('pwa_description', ''),
            'lang' => 'nl',
            'start_url' => '/dashboard',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => (string) static::get('background', '#FAFAF9'),
            'theme_color' => (string) static::get('color', '#E8231F'),
            'icons' => $icons,
            'shortcuts' => [
                ['name' => 'Nieuwe factuur', 'url' => '/invoices/create', 'icons' => [['src' => static::get('apple_touch'), 'sizes' => '180x180']]],
                ['name' => 'Nieuwe offerte', 'url' => '/offertes/create'],
                ['name' => 'Postvak IN (bonnetje scannen)', 'url' => '/inkoop/postvak'],
            ],
        ];
    }
}
