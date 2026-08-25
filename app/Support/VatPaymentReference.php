<?php

namespace App\Support;

/**
 * Betalingskenmerk voor de aangifte omzetbelasting.
 *
 * Bron: Belastingdienst, "Specificatie Betalingskenmerk bepaling" v1.5
 * (odb.belastingdienst.nl). Het kenmerk is 16 cijfers:
 *
 *   pos 1      controlecijfer (gewogen modulus 11)
 *   pos 2–9    eerste 8 cijfers van het fiscaal nummer (BSN/RSIN)
 *   pos 10     middelcode: 1 = aangifte omzetbelasting
 *   pos 11     laatste cijfer van het jaar
 *   pos 12–13  subnummer van het OB-nummer (B01 → 01)
 *   pos 14–15  tijdvakcode (maand 01–12, kwartaal 21/24/27/30, jaar 40)
 *   pos 16     volgnummer (0 voor de gewone aangifte)
 *
 * Gecontroleerd tegen de voorbeelden uit de specificatie:
 *   036000012 B 02 23 27 0  → 0036 0000 1130 2270 (OB, 3e kwartaal 2023)
 *   036000012 L 01 23 11 0  → 2036 0000 1630 1110 (LB, november 2023)
 */
class VatPaymentReference
{
    /** Rekeningnummer van de Belastingdienst voor btw (Rabobank, sinds 1 mei 2026). */
    public const IBAN = 'NL04 RABO 0200 1122 44';
    public const BIC = 'RABONL2U';
    public const BENEFICIARY = 'Belastingdienst';

    /** Gewichten voor de elfproef, toegepast van rechts naar links op de 15 cijfers. */
    private const WEIGHTS = [2, 4, 8, 5, 10, 9, 7, 3, 6, 1, 2, 4, 8, 5, 10];

    /**
     * Omzetbelastingnummer ontleden. Accepteert "NL123456789B01",
     * "123456789B01", "1234.56.789.B.01" en een kaal fiscaal nummer
     * (subnummer wordt dan 01). Geeft null bij onbruikbare invoer.
     *
     * @return array{fiscal: string, sub: string}|null
     */
    public static function parseObNumber(?string $raw): ?array
    {
        $clean = strtoupper(preg_replace('/[^0-9A-Z]/', '', (string) $raw) ?? '');
        $clean = preg_replace('/^NL/', '', $clean);

        if (preg_match('/^(\d{9})B(\d{2})$/', $clean, $m)) {
            return ['fiscal' => $m[1], 'sub' => $m[2]];
        }
        if (preg_match('/^(\d{9})$/', $clean, $m)) {
            return ['fiscal' => $m[1], 'sub' => '01'];
        }

        return null;
    }

    /** Tijdvakcode zoals in het aangiftenummer (pos. 5–6 van het JAVO-deel). */
    public static function periodCode(string $type, int $period): ?string
    {
        return match ($type) {
            'month' => ($period >= 1 && $period <= 12) ? sprintf('%02d', $period) : null,
            'quarter' => ($period >= 1 && $period <= 4) ? (string) (21 + 3 * ($period - 1)) : null,
            'year' => '40',
            default => null,
        };
    }

    /** Het 16-cijferige betalingskenmerk voor een aangiftetijdvak, of null. */
    public static function forPeriod(?string $obNumber, int $year, string $type, int $period, int $sequence = 0): ?string
    {
        $parsed = self::parseObNumber($obNumber);
        $code = self::periodCode($type, $period);
        if (! $parsed || ! $code) {
            return null;
        }

        $body = substr($parsed['fiscal'], 0, 8)   // pos 2–9
            . '1'                                   // pos 10: aangifte OB
            . substr((string) $year, -1)            // pos 11
            . $parsed['sub']                        // pos 12–13
            . $code                                 // pos 14–15
            . (string) max(0, min(9, $sequence));   // pos 16

        return self::checkDigit($body) . $body;
    }

    /** Controlecijfer (gewogen modulus 11) voor de 15 cijfers achter het controlecijfer. */
    public static function checkDigit(string $digits15): int
    {
        $sum = 0;
        for ($i = 0; $i < 15; $i++) {
            $sum += (int) $digits15[14 - $i] * self::WEIGHTS[$i];
        }
        $check = 11 - ($sum % 11);

        return match ($check) { 10 => 1, 11 => 0, default => $check };
    }

    /** Is dit een geldig (16-cijferig, kloppend) betalingskenmerk? */
    public static function isValid(?string $reference): bool
    {
        $digits = preg_replace('/\D/', '', (string) $reference) ?? '';

        return strlen($digits) === 16 && self::checkDigit(substr($digits, 1)) === (int) $digits[0];
    }

    /** Alleen de cijfers, of null als het geen 16 cijfers zijn. */
    public static function normalize(?string $reference): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $reference) ?? '';

        return strlen($digits) === 16 ? $digits : null;
    }

    /** Leesbaar: "0036 0000 1130 2270". */
    public static function format(?string $reference): ?string
    {
        $digits = self::normalize($reference);

        return $digits ? trim(chunk_split($digits, 4, ' ')) : null;
    }
}
