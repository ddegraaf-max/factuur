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

    /**
     * Talen uit een formulier (1.95): de lijst 'languages' en/of het oude
     * losse 'language'. Pools altijd en eerst, onbekende codes vallen weg.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public static function languageCodes(array $data): array
    {
        $codes = ['pl'];
        foreach ((array) ($data['languages'] ?? []) as $code) {
            if (is_string($code) && self::isLanguage($code)) {
                $codes[] = $code;
            }
        }
        $single = $data['language'] ?? null;
        if (is_string($single) && self::isLanguage($single)) {
            $codes[] = $single;
        }

        return array_values(array_unique($codes));
    }

    /** De voorkeurstaal voor schermen en mails: de eerste taal naast het Pools, anders 'pl'. @param  list<string>  $codes */
    public static function preferredLanguage(array $codes): string
    {
        foreach ($codes as $code) {
            if ($code !== 'pl') {
                return $code;
            }
        }

        return 'pl';
    }

    /** PKD-code in vaste vorm ("43.22.Z" → "4322Z"), of null. */
    public static function normalisePkd(?string $code): ?string
    {
        $code = strtoupper(str_replace(['.', ' ', '-'], '', (string) $code));

        return $code === '' ? null : $code;
    }

    /** Is deze PKD-code vakwerk (config fachora.trade_pkd_prefixes)? (1.96) */
    public static function isTradePkd(?string $code): bool
    {
        $code = self::normalisePkd($code);
        if ($code === null) {
            return false;
        }
        foreach ((array) config('fachora.trade_pkd_prefixes', []) as $prefix) {
            $prefix = (string) self::normalisePkd((string) $prefix);
            if ($prefix !== '' && str_starts_with($code, $prefix)) {
                return true;
            }
        }

        return false;
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
