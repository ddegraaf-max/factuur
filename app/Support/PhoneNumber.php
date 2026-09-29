<?php

namespace App\Support;

/**
 * Telefoonnummers voor sms: haalt uit wat iemand heeft ingevuld het mobiele
 * nummer, in de internationale schrijfwijze zonder plusteken (31612345678).
 * Een vast nummer kan geen sms ontvangen en levert niets op.
 */
class PhoneNumber
{
    /** Waarmee een Pools mobiel nummer begint; de rest zijn vaste nummers. */
    private const PL_MOBILE = '(?:45|50|51|53|57|60|66|69|72|73|78|79|88)';

    /** Het eerste mobiele nummer in de tekst, of null als er geen in staat. */
    public static function mobile(?string $raw): ?string
    {
        // In één veld staan soms twee nummers: "030 637 6170 / 06 40993654".
        foreach (preg_split('/[\/,;|]|\s+(?:of|en|or|lub)\s+/iu', (string) $raw) ?: [] as $part) {
            $number = self::normalise($part);
            if ($number !== null) {
                return $number;
            }
        }

        return null;
    }

    public static function isMobile(?string $raw): bool
    {
        return static::mobile($raw) !== null;
    }

    /** Leesbaar, voor op het scherm: +31 6 12345678. */
    public static function display(string $number): string
    {
        return str_starts_with($number, '316')
            ? '+31 6 ' . substr($number, 3)
            : '+' . $number;
    }

    private static function normalise(string $part): ?string
    {
        $part = trim($part);
        $international = str_starts_with($part, '+');
        $digits = preg_replace('/\D+/', '', $part) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
            $international = true;
        }

        $poland = Market::isPl();

        if (! $international) {
            if (preg_match('/^06\d{8}$/', $digits)) {
                return '31' . substr($digits, 1);
            }
            if (preg_match('/^316\d{8}$/', $digits)) {
                return $digits;
            }
            if ($poland && preg_match('/^' . self::PL_MOBILE . '\d{7}$/', $digits)) {
                return '48' . $digits;
            }
            if ($poland && preg_match('/^48' . self::PL_MOBILE . '\d{7}$/', $digits)) {
                return $digits;
            }

            return null;
        }

        // Met landcode: Nederland en Polen kennen we; van andere landen nemen we het nummer aan zoals het is.
        if (str_starts_with($digits, '31')) {
            // Soms staat de nul er nog tussen: +31 (0)6 …
            $digits = preg_replace('/^310/', '31', $digits);

            return preg_match('/^316\d{8}$/', $digits) ? $digits : null;
        }
        if (str_starts_with($digits, '48')) {
            return preg_match('/^48' . self::PL_MOBILE . '\d{7}$/', $digits) ? $digits : null;
        }

        return strlen($digits) >= 10 && strlen($digits) <= 15 ? $digits : null;
    }
}
