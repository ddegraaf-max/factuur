<?php

namespace App\Casts;

use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Een datum zonder tijd — ook in de opslag.
 *
 * De gewone date-cast van Eloquent leest een kolom als dag, maar schrijft hem
 * weg als 'Y-m-d H:i:s'. Postgres knipt die tijd eraf, want de kolom is een
 * date; sqlite bewaart de tekst precies zoals hij komt. Een boeking van
 * 31 december staat daar dan als "2025-12-31 00:00:00", en dat is als tekst
 * gróter dan "2025-12-31" — de bovengrens van elk whereBetween op het jaar.
 *
 * Zo viel in de testsuite de afsluitboeking buiten het jaar dat ze afsloot, en
 * bleef het resultaat na het vaststellen staan. Op productie gebeurde dat niet.
 * Maar een test die iets anders doet dan productie bewijst niets, en een
 * boeking op de laatste dag van een periode is geen randgeval: dat is elke
 * jaarafsluiting.
 */
class DateOnly implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->carbon($value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->carbon($value)->toDateString();
    }

    private function carbon(mixed $value): Carbon
    {
        return $value instanceof DateTimeInterface ? Carbon::instance($value) : Carbon::parse((string) $value);
    }
}
