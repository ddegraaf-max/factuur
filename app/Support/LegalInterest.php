<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Wettelijke incassokosten en wettelijke rente (Nederland), voor de calculator
 * op /incassokosten-berekenen. Percentages en staffel staan in config/rente.php.
 *
 * Rente loopt vanaf de dag na de vervaldatum tot en met de gekozen dag, per
 * dag naar het percentage van dat moment. Na elk vol jaar komt de rente van
 * dat jaar bij het bedrag waarover verder wordt gerekend (art. 6:119 lid 2 en
 * 6:119a lid 3 BW).
 */
class LegalInterest
{
    /** Buitengerechtelijke incassokosten volgens de staffel, exclusief btw. */
    public static function collectionCosts(float $principal): float
    {
        if ($principal <= 0) {
            return 0.0;
        }

        $costs = 0.0;
        $rest = $principal;
        foreach (config('rente.scale') as [$band, $percentage]) {
            $part = $band === null ? $rest : min($rest, $band);
            $costs += $part * $percentage / 100;
            $rest -= $part;
            if ($rest <= 0) {
                break;
            }
        }

        return round(min(max($costs, (float) config('rente.minimum')), (float) config('rente.maximum')), 2);
    }

    /** Het percentage dat op deze dag geldt. */
    public static function rateOn(Carbon $day, bool $business): float
    {
        $rate = 0.0;
        foreach (self::rates($business) as $from => $percentage) {
            if ($day->toDateString() >= $from) {
                $rate = (float) $percentage;
            }
        }

        return $rate;
    }

    /**
     * Rente over de hoofdsom, uitgesplitst per periode met hetzelfde percentage
     * en hetzelfde bedrag.
     *
     * @return array{total: float, days: int, periods: list<array{from: Carbon, to: Carbon, days: int, rate: float, base: float, interest: float}>}
     */
    public static function interest(float $principal, Carbon $dueDate, Carbon $until, bool $business): array
    {
        $start = $dueDate->copy()->startOfDay()->addDay();
        $end = $until->copy()->startOfDay()->addDay();  // tot en met de gekozen dag

        if ($principal <= 0 || $start->gte($end)) {
            return ['total' => 0.0, 'days' => 0, 'periods' => []];
        }

        // Knippunten: een nieuw percentage, een nieuw kalenderjaar (365 of 366 dagen) en
        // elke verjaardag van het verzuim (dan komt de rente bij de hoofdsom).
        $cuts = [];
        foreach (array_keys(self::rates($business)) as $from) {
            $cuts[] = $from;
        }
        for ($year = $start->year + 1; $year <= $end->year; $year++) {
            $cuts[] = $year . '-01-01';
        }
        $anniversaries = [];
        for ($n = 1; ($day = $start->copy()->addYearsNoOverflow($n))->lt($end); $n++) {
            $anniversaries[] = $cuts[] = $day->toDateString();
        }
        $cuts = array_values(array_unique(array_filter(
            $cuts,
            fn (string $cut) => $cut > $start->toDateString() && $cut < $end->toDateString()
        )));
        sort($cuts);
        $cuts[] = $end->toDateString();

        $periods = [];
        $base = $principal;
        $thisYear = 0.0;
        $total = 0.0;
        $from = $start->copy();

        foreach ($cuts as $cut) {
            $to = Carbon::parse($cut);
            $days = (int) round($from->diffInDays($to, true));
            $rate = self::rateOn($from, $business);
            $interest = $base * $rate / 100 * $days / ($from->isLeapYear() ? 366 : 365);

            $last = $periods ? count($periods) - 1 : null;
            if ($last !== null && $periods[$last]['rate'] === $rate && abs($periods[$last]['base'] - $base) < 0.00001) {
                $periods[$last]['to'] = $to->copy()->subDay();
                $periods[$last]['days'] += $days;
                $periods[$last]['interest'] += $interest;
            } else {
                $periods[] = ['from' => $from->copy(), 'to' => $to->copy()->subDay(), 'days' => $days, 'rate' => $rate, 'base' => $base, 'interest' => $interest];
            }

            $thisYear += $interest;
            $total += $interest;
            if (in_array($cut, $anniversaries, true)) {
                $base += $thisYear;
                $thisYear = 0.0;
            }
            $from = $to;
        }

        return [
            'total' => round($total, 2),
            'days' => (int) round($start->diffInDays($end, true)),
            'periods' => array_map(fn (array $p) => ['base' => round($p['base'], 2), 'interest' => round($p['interest'], 2)] + $p, $periods),
        ];
    }

    /**
     * De hele berekening voor de calculator.
     *
     * @return array<string, mixed>
     */
    public static function calculate(float $principal, Carbon $dueDate, Carbon $until, bool $business, bool $addVat = false): array
    {
        $costs = self::collectionCosts($principal);
        $vat = $addVat ? round($costs * 0.21, 2) : 0.0;
        $interest = self::interest($principal, $dueDate, $until, $business);

        return [
            'principal' => round($principal, 2),
            'costs' => $costs,
            'costs_vat' => $vat,
            'interest' => $interest['total'],
            'days' => $interest['days'],
            'periods' => $interest['periods'],
            'rate_now' => self::rateOn($until, $business),
            'total' => round($principal + $costs + $vat + $interest['total'], 2),
        ];
    }

    /** @return array<string, float> ingangsdatum => percentage, oplopend */
    public static function rates(bool $business): array
    {
        $rates = config($business ? 'rente.business' : 'rente.consumer', []);
        ksort($rates);

        return $rates;
    }
}
