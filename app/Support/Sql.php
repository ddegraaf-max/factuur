<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Kleine SQL-fragmenten die per database anders zijn. Productie draait op
 * Postgres, de tests en de lokale preview op SQLite — `EXTRACT(… FROM …)`
 * bestaat daar niet, `strftime` wel.
 */
class Sql
{
    /** Maandnummer (1–12) van een datumkolom, als integer. */
    public static function month(string $column): string
    {
        return static::driver() === 'sqlite'
            ? "CAST(strftime('%m', {$column}) AS INTEGER)"
            : "EXTRACT(MONTH FROM {$column})";
    }

    /** Jaartal van een datumkolom, als integer. */
    public static function year(string $column): string
    {
        return static::driver() === 'sqlite'
            ? "CAST(strftime('%Y', {$column}) AS INTEGER)"
            : "EXTRACT(YEAR FROM {$column})";
    }

    private static function driver(): string
    {
        return (string) DB::connection()->getDriverName();
    }
}
