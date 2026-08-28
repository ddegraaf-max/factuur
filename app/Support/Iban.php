<?php

namespace App\Support;

/** IBAN-hulpjes: normaliseren, controleren (mod 97) en netjes weergeven. */
class Iban
{
    public static function normalize(?string $iban): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $iban));
    }

    public static function valid(?string $iban): bool
    {
        $iban = static::normalize($iban);
        if (strlen($iban) < 15 || strlen($iban) > 34 || ! preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]+$/', $iban)) {
            return false;
        }
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $numeric = '';
        foreach (str_split($rearranged) as $ch) {
            $numeric .= ctype_alpha($ch) ? (string) (ord($ch) - 55) : $ch;
        }
        // Mod 97 in stukken, zodat het ook zonder bcmath werkt.
        $remainder = 0;
        foreach (str_split($numeric, 7) as $chunk) {
            $remainder = (int) (($remainder . $chunk) % 97);
        }

        return $remainder === 1;
    }

    public static function format(?string $iban): string
    {
        return trim(chunk_split(static::normalize($iban), 4, ' '));
    }
}
