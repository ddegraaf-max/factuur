<?php

namespace App\Services\Fachora;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Postcode en plaats naar coördinaten, voor de afstand tussen klant en
 * bedrijf. Nominatim (OpenStreetMap) met een nette User-Agent en een cache
 * die nooit verloopt; valt terug op de stadstabel in config/fachora.php.
 */
class GeocodeService
{
    /** @return array{lat: float, lng: float}|null */
    public function lookup(?string $postal, ?string $city): ?array
    {
        $postal = trim((string) $postal);
        $city = trim((string) $city);
        if ($postal === '' && $city === '') {
            return null;
        }

        $key = 'fachora:geo:' . md5(mb_strtolower($postal . '|' . $city));
        $hit = Cache::get($key);
        if (is_array($hit)) {
            return $hit;
        }

        $hit = $this->remote($postal, $city) ?? $this->fallback($city);
        if ($hit !== null) {
            Cache::forever($key, $hit);
        }

        return $hit;
    }

    /** @return array{lat: float, lng: float}|null */
    private function remote(string $postal, string $city): ?array
    {
        try {
            $response = Http::timeout(6)
                ->withHeaders(['User-Agent' => 'Fachora/1.0 (' . config('fachora.geocode_contact') . ')'])
                ->acceptJson()
                ->get(config('fachora.geocode_url') . '/search', array_filter([
                    'format' => 'jsonv2',
                    'countrycodes' => 'pl',
                    'limit' => 1,
                    'postalcode' => $postal ?: null,
                    'city' => $city ?: null,
                ]));
            $first = $response->ok() ? ($response->json()[0] ?? null) : null;
            if (is_array($first) && isset($first['lat'], $first['lon'])) {
                return ['lat' => round((float) $first['lat'], 6), 'lng' => round((float) $first['lon'], 6)];
            }
        } catch (\Throwable $e) {
            Log::info('Geocoderen mislukt', ['postal' => $postal, 'city' => $city, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /** @return array{lat: float, lng: float}|null */
    public function fallback(?string $city): ?array
    {
        $name = self::normalise($city);
        if ($name === '') {
            return null;
        }
        $cities = (array) config('fachora.cities', []);
        $hit = $cities[$name] ?? null;
        if ($hit === null) {
            // "Łódź-Bałuty", "Warszawa Mokotów": het eerste woord.
            $first = preg_split('/[\s\-]+/', $name)[0] ?? '';
            $hit = $cities[$first] ?? null;
        }

        return $hit ? ['lat' => (float) $hit[0], 'lng' => (float) $hit[1]] : null;
    }

    public static function normalise(?string $city): string
    {
        return trim(mb_strtolower(Str::ascii((string) $city)));
    }
}
