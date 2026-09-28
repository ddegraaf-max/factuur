<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Weken in de uitvraagtool. Opgeslagen als ISO-week ('2026-W44'); getoond als
 * weeknummer met de dagen erbij, zodat niemand een weekkalender nodig heeft.
 */
class IsoWeek
{
    /** Maandag van '2026-W44', of null als de tekst geen ISO-week is. */
    public static function monday(?string $week): ?Carbon
    {
        if (! preg_match('/^(\d{4})-W(\d{1,2})$/i', trim((string) $week), $m) || (int) $m[2] < 1 || (int) $m[2] > 53) {
            return null;
        }

        return Carbon::now()->setISODate((int) $m[1], (int) $m[2])->startOfDay();
    }

    /**
     * Voor achter het woord 'week': "44 (26 okt – 1 nov 2026)". Een notatie
     * die we niet herkennen (oudere, vrij ingevulde tekst) blijft staan zoals hij is.
     */
    public static function label(?string $week): ?string
    {
        if (blank($week)) {
            return null;
        }
        $monday = static::monday($week);
        if (! $monday) {
            return trim($week);
        }
        $sunday = $monday->copy()->addDays(6);

        return $monday->isoWeek . ' (' . $monday->translatedFormat('j M') . ' – ' . $sunday->translatedFormat('j M Y') . ')';
    }
}
