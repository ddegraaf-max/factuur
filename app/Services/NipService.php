<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Poolse bedrijfsgegevens opzoeken op NIP in de "Wykaz podatników VAT"
 * (biała lista) van het Ministerstwo Finansów — gratis, zonder sleutel.
 * De tegenhanger van KvkService voor de Poolse markt: naam, adres, REGON,
 * btw-status en de aangemelde bankrekeningen.
 */
class NipService
{
    public const BASE = 'https://wl-api.mf.gov.pl/api/search/nip/';

    /** Controleert de NIP-controlecijfers (gewichten 6 5 7 2 3 4 5 6 7, modulo 11). */
    public static function valid(?string $nip): bool
    {
        $digits = preg_replace('/\D/', '', (string) $nip);
        if (strlen($digits) !== 10) {
            return false;
        }
        $weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
        $sum = 0;
        foreach ($weights as $i => $w) {
            $sum += $w * (int) $digits[$i];
        }

        return ($sum % 11) === (int) $digits[9];
    }

    public static function normalize(?string $nip): string
    {
        return preg_replace('/\D/', '', (string) $nip);
    }

    /**
     * @return array{nip:string,name:string,regon:?string,address:?string,postal_code:?string,city:?string,vat_status:?string,accounts:string[]}|null
     */
    public function lookup(string $nip): ?array
    {
        $nip = self::normalize($nip);
        if (! self::valid($nip)) {
            throw new \DomainException('Nieprawidłowy numer NIP.');
        }

        return Cache::remember('nip:' . $nip, now()->addHours(12), function () use ($nip) {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get(self::BASE . $nip, ['date' => now()->toDateString()]);

            if (! $response->ok()) {
                throw new \DomainException('Wykaz podatników VAT jest chwilowo niedostępny. Spróbuj ponownie za chwilę.');
            }

            $subject = $response->json('result.subject');
            if (! $subject) {
                return null;
            }

            $address = $subject['workingAddress'] ?? $subject['residenceAddress'] ?? null;
            [$street, $postal, $city] = self::splitAddress($address);

            return [
                'nip' => $nip,
                'name' => (string) ($subject['name'] ?? ''),
                'regon' => $subject['regon'] ?? null,
                'address' => $street,
                'postal_code' => $postal,
                'city' => $city,
                'vat_status' => $subject['statusVat'] ?? null, // Czynny / Zwolniony / Niezarejestrowany
                'accounts' => array_values(array_filter((array) ($subject['accountNumbers'] ?? []))),
            ];
        });
    }

    /** "ul. Prosta 51, 00-838 Warszawa" → [straat, postcode, plaats]. */
    public static function splitAddress(?string $address): array
    {
        if (! $address) {
            return [null, null, null];
        }
        if (preg_match('/^(.*?),\s*(\d{2}-\d{3})\s+(.+)$/u', trim($address), $m)) {
            return [trim($m[1]), $m[2], trim($m[3])];
        }

        return [trim($address), null, null];
    }
}
