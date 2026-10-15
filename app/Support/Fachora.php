<?php

namespace App\Support;

/**
 * Kleine hulpfuncties rond config/fachora.php: categorieën, talen, afstand.
 */
class Fachora
{
    /** @return array<string, array<string, mixed>> */
    public static function categories(): array
    {
        return (array) config('fachora.categories', []);
    }

    public static function hasCategory(?string $key): bool
    {
        return $key !== null && isset(self::categories()[$key]);
    }

    public static function categoryName(string $key, ?string $locale = null): string
    {
        $locale = self::locale($locale);

        return (string) (self::categories()[$key][$locale] ?? $key);
    }

    /** Categorieën voor de schermen: [{key, name, icon, examples}]. @return list<array<string, mixed>> */
    public static function categoryList(?string $locale = null): array
    {
        $locale = self::locale($locale);

        return collect(self::categories())->map(fn ($c, $key) => [
            'key' => $key,
            'name' => $c[$locale] ?? $key,
            'icon' => $c['icon'] ?? 'tools',
            'examples' => $c['examples'][$locale] ?? '',
        ])->values()->all();
    }

    /** @return list<array<string, string>> */
    public static function languageList(?string $locale = null): array
    {
        $locale = self::locale($locale);

        return collect((array) config('fachora.languages', []))->map(fn ($l, $code) => [
            'code' => $code, 'name' => $l[$locale] ?? strtoupper($code), 'flag' => $l['flag'] ?? '',
        ])->values()->all();
    }

    /** @return list<array<string, string>> */
    public static function levelList(?string $locale = null): array
    {
        $locale = self::locale($locale);

        return collect((array) config('fachora.levels', []))->map(fn ($l, $code) => ['code' => $code, 'name' => $l[$locale] ?? $code])->values()->all();
    }

    public static function isLanguage(?string $code): bool
    {
        return $code !== null && isset(((array) config('fachora.languages', []))[$code]);
    }

    /** Afstand in kilometers tussen twee punten (haversine). */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1.0, sqrt($a)));
    }

    /** Bedrag in złoty voor op het scherm (1 234,50 zł). */
    public static function money(float|int|null $amount): string
    {
        return $amount === null ? '' : number_format((float) $amount, 2, ',', "\u{00A0}") . "\u{00A0}zł";
    }

    private static function locale(?string $locale): string
    {
        $locale = $locale ?: app()->getLocale();

        return $locale === 'en' ? 'en' : 'pl';
    }
}
