<?php

namespace App\Services;

use App\Support\Market;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wisselkoersen van de Narodowy Bank Polski (tabel A, gemiddelde koers).
 *
 * De Poolse wet op betalingsachterstanden (art. 10 ust. 1) rekent de vaste
 * vergoeding van 40/70/100 EUR om naar złoty tegen de gemiddelde NBP-koers
 * van de LAATSTE WERKDAG VAN DE MAAND VÓÓR de maand waarin de vordering
 * opeisbaar werd. Deze service haalt precies die koers op (api.nbp.pl, gratis,
 * geen sleutel), bewaart hem permanent in de cache (historische koersen
 * veranderen niet) en valt terug op de vaste koers uit config/markets.php
 * ('eur_pln') als de API niet bereikbaar is.
 */
class NbpService
{
    public const API = 'https://api.nbp.pl/api/exchangerates/rates/a/eur';

    /**
     * EUR-koers voor een vordering die opeisbaar werd op $dueDate.
     *
     * @return array{rate: float, date: ?string, source: 'nbp'|'fallback'}
     */
    public function eurRateForDueDate(Carbon $dueDate): array
    {
        // Laatste kalenderdag van de voorgaande maand; de API geeft ons de laatste werkdag daarvóór.
        $target = $dueDate->copy()->startOfMonth()->subDay();

        return $this->eurRateOnOrBefore($target);
    }

    /**
     * Gemiddelde EUR-koers op de laatste werkdag op of vóór $date.
     *
     * @return array{rate: float, date: ?string, source: 'nbp'|'fallback'}
     */
    public function eurRateOnOrBefore(Carbon $date): array
    {
        $date = $date->copy()->startOfDay();
        $key = 'nbp:eur:' . $date->toDateString();

        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->fetchOnOrBefore($date);
        if ($result !== null) {
            // Koersen uit het verleden veranderen niet meer; die van vandaag kan nog volgen (tabel ±12:00).
            if ($date->lt(now()->startOfDay())) {
                Cache::forever($key, $result);
            } else {
                Cache::put($key, $result, now()->addHours(6));
            }

            return $result;
        }

        return $this->fallback();
    }

    /** @return array{rate: float, date: null, source: 'fallback'} */
    public function fallback(): array
    {
        $env = config('markets.overrides.eur_pln');
        $rate = $env !== null && $env !== '' ? (float) $env : (float) Market::get('eur_pln', 4.30);

        return ['rate' => $rate, 'date' => null, 'source' => 'fallback'];
    }

    /**
     * Vraagt een venster van twee weken tot en met $date op en neemt de laatste
     * notering: zo landen we vanzelf op de laatste werkdag (weekend, feestdagen).
     *
     * @return array{rate: float, date: string, source: 'nbp'}|null
     */
    private function fetchOnOrBefore(Carbon $date): ?array
    {
        $from = $date->copy()->subDays(14)->toDateString();
        $to = $date->toDateString();

        try {
            $response = Http::timeout(4)->acceptJson()->get(self::API . "/{$from}/{$to}/", ['format' => 'json']);
            if (! $response->successful()) {
                return null;
            }
            $rates = $response->json('rates');
            if (! is_array($rates) || $rates === []) {
                return null;
            }
            $last = end($rates);
            $mid = (float) ($last['mid'] ?? 0);
            if ($mid <= 0) {
                return null;
            }

            return ['rate' => $mid, 'date' => (string) $last['effectiveDate'], 'source' => 'nbp'];
        } catch (\Throwable $e) {
            Log::warning('NBP-koers ophalen mislukt', ['date' => $to, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
