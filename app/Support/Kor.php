<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Invoice;

/**
 * Kleineondernemersregeling (KOR): de vrijstelling van omzetbelasting voor
 * ondernemers met hoogstens 20.000 euro omzet per kalenderjaar (artikel 25
 * Wet OB). Wie meedoet rekent geen btw, doet geen btw-aangifte en kan geen
 * btw terugvragen. Op een factuur staat geen tarief en geen btw-bedrag, wel
 * de vermelding dat de vrijstelling geldt.
 *
 * Alleen in de Nederlandse markt.
 */
class Kor
{
    /** De omzetgrens per kalenderjaar; daarboven moet de ondernemer zich afmelden. */
    public const LIMIT = 20000.0;

    /** Vanaf dit deel van de grens waarschuwen we. */
    public const WARN_AT = 0.8;

    public static function available(): bool
    {
        return ! Market::isPl();
    }

    public static function applies(?Company $company): bool
    {
        return self::available() && (bool) $company?->kor;
    }

    /**
     * Omzet van dit kalenderjaar tegenover de grens. Creditnota's tellen als
     * aftrek; concepten tellen niet mee.
     *
     * @return array{year: int, revenue: float, limit: float, percent: int, state: string}
     */
    public static function status(Company $company): array
    {
        $year = (int) now()->year;
        $revenue = (float) Invoice::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereYear('invoice_date', $year)
            ->selectRaw('COALESCE(SUM(CASE WHEN is_credit THEN -subtotal ELSE subtotal END), 0) AS revenue')
            ->value('revenue');

        return [
            'year' => $year,
            'revenue' => round($revenue, 2),
            'limit' => self::LIMIT,
            'percent' => (int) min(100, max(0, round($revenue / self::LIMIT * 100))),
            'state' => match (true) {
                $revenue > self::LIMIT => 'over',
                $revenue >= self::LIMIT * self::WARN_AT => 'near',
                default => 'ok',
            },
        ];
    }
}
