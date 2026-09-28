<?php

namespace App\Support;

use App\Models\Customer;
use Illuminate\Validation\ValidationException;

/**
 * Btw verlegd: de afnemer draagt de btw af, niet de verkoper. Dat geldt onder
 * meer voor onderaannemers in de bouw en voor diensten aan ondernemers in een
 * ander EU-land. Op het document staan dan geen btw-bedragen, wel de
 * vermelding 'btw verlegd' en het btw-nummer van de afnemer.
 *
 * Alleen in de Nederlandse markt; de Poolse factuur (KSeF) kent eigen regels.
 */
class VatReverse
{
    public static function available(): bool
    {
        return ! Market::isPl();
    }

    /** Is btw verlegd aangevraagd (en hier mogelijk)? */
    public static function requested(array $data): bool
    {
        return self::available() && ! empty($data['vat_reversed']);
    }

    /**
     * Alle regels naar 0%.
     *
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    public static function lines(array $lines, bool $reversed): array
    {
        if (! $reversed) {
            return $lines;
        }

        return array_map(fn ($line) => ['vat_rate' => 0] + (array) $line, $lines);
    }

    /**
     * Gevalideerde formulierdata afmaken: bij btw verlegd de regels op 0% en het
     * btw-nummer van de klant op orde. Staat dat nummer nog niet bij de klant,
     * dan mag het op het formulier worden ingevuld; het wordt bij de klant bewaard.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function prepare(array $data): array
    {
        // Kleineondernemersregeling: de vrijstelling geldt, er valt niets te verleggen.
        $customer = Customer::find($data['customer_id'] ?? null);
        if (Kor::applies($customer?->company)) {
            $data['vat_reversed'] = false;
            $data['lines'] = self::lines($data['lines'] ?? [], true);
            unset($data['customer_vat_number']);

            return $data;
        }

        $data['vat_reversed'] = self::requested($data);
        if (! $data['vat_reversed']) {
            unset($data['customer_vat_number']);

            return $data;
        }

        $data['lines'] = self::lines($data['lines'] ?? [], true);

        $entered = self::normalize((string) ($data['customer_vat_number'] ?? ''));
        unset($data['customer_vat_number']);

        if ($customer && $entered !== '' && $entered !== self::normalize((string) $customer->vat_number)) {
            if (! self::plausible($entered)) {
                throw ValidationException::withMessages([
                    'customer_vat_number' => __('Vul een geldig btw-nummer in, met de landcode ervoor (bijvoorbeeld NL123456789B01).'),
                ]);
            }
            $customer->forceFill(['vat_number' => $entered])->save();
        }

        if (! $customer || self::normalize((string) $customer->vat_number) === '') {
            throw ValidationException::withMessages([
                'customer_vat_number' => __('Bij btw verlegd hoort het btw-nummer van de klant op het document. Vul het hier in.'),
            ]);
        }

        return $data;
    }

    /**
     * Ziet het eruit als een btw-nummer? Nederlandse nummers hebben een vaste
     * vorm; voor andere landen: landcode, dan letters en cijfers met minstens
     * vijf cijfers. Of het nummer bestaat, controleren we niet.
     */
    public static function plausible(string $number): bool
    {
        if (str_starts_with($number, 'NL')) {
            return (bool) preg_match('/^NL\d{9}B\d{2}$/', $number);
        }

        return (bool) preg_match('/^[A-Z]{2}[A-Z0-9]{2,13}$/', $number)
            && preg_match_all('/\d/', $number) >= 5;
    }

    public static function normalize(string $number): string
    {
        return strtoupper(preg_replace('/[\s.\-]/', '', $number) ?? '');
    }
}
