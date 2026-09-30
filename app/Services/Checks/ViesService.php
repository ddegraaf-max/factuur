<?php

namespace App\Services\Checks;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Is een btw-nummer geldig? Via VIES van de Europese Commissie, zonder sleutel.
 * Zegt alleen of het nummer vandaag geldig is, met (voor de meeste landen) de
 * naam en het adres. Niets over betaalgedrag.
 */
class ViesService
{
    private const URL = 'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number';

    /**
     * @return array{valid: bool, name: ?string, address: ?string, checked_at: string}|null null als het nummer niet te controleren is
     */
    public function check(?string $vatNumber, bool $fresh = false): ?array
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $vatNumber) ?? '');
        if (! preg_match('/^([A-Z]{2})([A-Z0-9+*]{2,12})$/', $clean, $m)) {
            return null;
        }
        [, $country, $number] = $m;
        // Griekenland heet in VIES EL.
        $country = $country === 'GR' ? 'EL' : $country;
        if ($fresh) {
            Cache::forget('vies:' . $country . $number);
        }

        return Cache::remember('vies:' . $country . $number, now()->addDays(7), function () use ($country, $number) {
            try {
                $response = Http::acceptJson()->asJson()->timeout(10)
                    ->post(self::URL, ['countryCode' => $country, 'vatNumber' => $number]);
            } catch (\Throwable $e) {
                Log::info('VIES niet bereikbaar', ['error' => $e->getMessage()]);

                return null;
            }
            if (! $response->successful() || $response->json('userError', 'VALID') !== 'VALID' && $response->json('valid') === null) {
                Log::info('VIES gaf geen antwoord', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 200)]);

                return null;
            }
            $valid = $response->json('valid') ?? $response->json('isValid');
            if ($valid === null) {
                return null;
            }
            $name = trim((string) $response->json('name'));
            $address = trim(preg_replace('/\s+/', ' ', (string) $response->json('address')) ?? '');

            return [
                'valid' => (bool) $valid,
                'name' => $name !== '' && $name !== '---' ? $name : null,
                'address' => $address !== '' && $address !== '---' ? $address : null,
                'checked_at' => now()->toIso8601String(),
            ];
        });
    }
}
