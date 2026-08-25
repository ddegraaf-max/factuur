<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\VatFiling;
use App\Support\VatPaymentReference;
use Carbon\Carbon;

/**
 * Btw-aangifte "aangifte-klaar": per tijdvak (maand, kwartaal of jaar) alle
 * rubrieken van het aangifteformulier omzetbelasting, precies in de indeling
 * van Mijn Belastingdienst Zakelijk, zodat overnemen een kwestie van
 * overtypen (of kopiëren) is.
 *
 *  - 1a/1b: factuurregels 21% / 9% (factuurstelsel: op factuurdatum).
 *  - 0%-regels worden op klantland verdeeld: NL → 1e, EU → 3b, overig → 3a.
 *  - 5b (voorbelasting): btw op ingeboekte inkoopfacturen.
 *  - Wat EasyInvoice niet kan weten (1c, 1d, 2a, 3c, 4a, 4b en voorbelasting
 *    buiten Easy) vult de ondernemer per tijdvak zelf in; dat wordt bewaard.
 *  - Afronding zoals de Belastingdienst toestaat: in je voordeel — te betalen
 *    btw en grondslagen naar beneden, voorbelasting naar boven.
 *
 * Creditnota's tellen negatief mee. Concepten en geannuleerde facturen niet.
 */
class VatService
{
    /** EU-lidstaten (ISO 3166-1 alpha-2). */
    public const EU = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE',
        'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
    ];

    public const PERIOD_TYPES = ['quarter', 'month', 'year'];

    /** Rubrieken in de volgorde van het aangifteformulier. */
    public const RUBRIEKEN = [
        '1a' => ['label' => 'Leveringen/diensten belast met hoog tarief', 'source' => 'auto'],
        '1b' => ['label' => 'Leveringen/diensten belast met laag tarief', 'source' => 'auto'],
        '1c' => ['label' => 'Leveringen/diensten belast met overige tarieven, behalve 0%', 'source' => 'manual'],
        '1d' => ['label' => 'Privégebruik', 'source' => 'manual'],
        '1e' => ['label' => 'Leveringen/diensten belast met 0% of niet bij u belast', 'source' => 'auto', 'no_vat' => true],
        '2a' => ['label' => 'Leveringen/diensten waarbij de btw naar u is verlegd', 'source' => 'manual'],
        '3a' => ['label' => 'Leveringen naar landen buiten de EU (uitvoer)', 'source' => 'auto', 'no_vat' => true],
        '3b' => ['label' => 'Leveringen naar of diensten in landen binnen de EU', 'source' => 'auto', 'no_vat' => true],
        '3c' => ['label' => 'Installatie/afstandsverkopen binnen de EU', 'source' => 'manual', 'no_vat' => true],
        '4a' => ['label' => 'Leveringen/diensten uit landen buiten de EU', 'source' => 'manual'],
        '4b' => ['label' => 'Leveringen/diensten uit landen binnen de EU', 'source' => 'manual'],
        '5a' => ['label' => 'Verschuldigde btw (rubrieken 1 t/m 4)', 'source' => 'total'],
        '5b' => ['label' => 'Voorbelasting', 'source' => 'input'],
        '5c' => ['label' => 'Subtotaal (5a min 5b)', 'source' => 'total'],
    ];

    /** Rubrieken waarvan de btw in 5a wordt opgeteld. */
    private const OWED = ['1a', '1b', '1c', '1d', '2a', '4a', '4b'];

    /** Rubrieken die de ondernemer zelf mag invullen (base/vat). */
    public const MANUAL = ['1c', '1d', '2a', '3c', '4a', '4b', '5b'];

    public static function periodType(?Company $company): string
    {
        $type = $company?->vat_period;

        return in_array($type, self::PERIOD_TYPES, true) ? $type : 'quarter';
    }

    /** Tijdvakken van een jaar: label, begin, einde en uiterste aangiftedatum. */
    public function periods(string $type, int $year): array
    {
        $count = match ($type) { 'month' => 12, 'year' => 1, default => 4 };
        $quarterMonths = [1 => 'jan – mrt', 2 => 'apr – jun', 3 => 'jul – sep', 4 => 'okt – dec'];
        $list = [];

        for ($p = 1; $p <= $count; $p++) {
            [$start, $end] = match ($type) {
                'month' => [Carbon::create($year, $p, 1)->startOfDay(), Carbon::create($year, $p, 1)->endOfMonth()->endOfDay()],
                'year' => [Carbon::create($year, 1, 1)->startOfDay(), Carbon::create($year, 12, 31)->endOfDay()],
                default => [
                    Carbon::create($year, ($p - 1) * 3 + 1, 1)->startOfDay(),
                    Carbon::create($year, ($p - 1) * 3 + 1, 1)->addMonths(2)->endOfMonth()->endOfDay(),
                ],
            };

            // Aangifte én betaling moeten binnen een maand na afloop van het
            // tijdvak binnen zijn (Q1 → 30 april); jaaraangifte vóór 1 april.
            $deadline = $type === 'year'
                ? Carbon::create($year + 1, 3, 31)->endOfDay()
                : $end->copy()->addDay()->endOfMonth()->endOfDay();

            $list[$p] = [
                'type' => $type,
                'period' => $p,
                'year' => $year,
                'label' => match ($type) {
                    'month' => ucfirst($start->translatedFormat('F')),
                    'year' => "Jaar {$year}",
                    default => "{$p}e kwartaal",
                },
                'months' => match ($type) {
                    'month' => $start->translatedFormat('F Y'),
                    'year' => "jan – dec {$year}",
                    default => $quarterMonths[$p] . " {$year}",
                },
                'start' => $start,
                'end' => $end,
                'deadline' => $deadline,
            ];
        }

        return $list;
    }

    /** In welk tijdvak (1..n) valt deze datum? */
    public function periodIndex(string $type, Carbon $date): int
    {
        return match ($type) {
            'month' => $date->month,
            'year' => 1,
            default => (int) ceil($date->month / 3),
        };
    }

    /**
     * Volledig overzicht voor een jaar. Met $withDetails ook de onderliggende
     * verkoop- en inkoopfacturen per tijdvak (de onderbouwing per rubriek).
     */
    public function overview(Company $company, int $year, bool $withDetails = true): array
    {
        $type = self::periodType($company);
        $periods = $this->periods($type, $year);

        $emptyAuto = fn () => [
            '1a' => ['base' => 0.0, 'vat' => 0.0], '1b' => ['base' => 0.0, 'vat' => 0.0],
            '1e' => ['base' => 0.0, 'vat' => 0.0], '3a' => ['base' => 0.0, 'vat' => 0.0],
            '3b' => ['base' => 0.0, 'vat' => 0.0],
        ];
        $acc = [];
        foreach ($periods as $p => $meta) {
            $acc[$p] = [
                'auto' => $emptyAuto(), 'input_vat' => 0.0,
                'invoice_count' => 0, 'credit_count' => 0, 'purchase_count' => 0,
                'invoices' => [], 'purchases' => [],
            ];
        }

        // Expliciet op company filteren: dit draait ook vanuit de console
        // (herinneringen), waar de auth-scope niet grijpt.
        $invoices = Invoice::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereYear('invoice_date', $year)
            ->with('lines')
            ->orderBy('invoice_date')->orderBy('id')
            ->get();

        foreach ($invoices as $invoice) {
            $p = $this->periodIndex($type, $invoice->invoice_date);
            if (! isset($acc[$p])) {
                continue;
            }
            $sign = $invoice->is_credit ? -1 : 1;
            $country = strtoupper(trim((string) $invoice->customer_country)) ?: 'NL';
            // 0%-regels: binnenland → 1e, EU → 3b (ICP), daarbuiten → 3a (uitvoer).
            $zeroKey = $country === 'NL' ? '1e' : (in_array($country, self::EU, true) ? '3b' : '3a');

            $tags = [];
            foreach ($invoice->lines as $line) {
                $rate = (int) round((float) $line->vat_rate);
                $key = $rate === 21 ? '1a' : ($rate === 9 ? '1b' : $zeroKey);
                $acc[$p]['auto'][$key]['base'] += $sign * (float) $line->line_subtotal;
                $acc[$p]['auto'][$key]['vat'] += $sign * (float) $line->line_vat;
                $tags[$key] = true;
            }

            $invoice->is_credit ? $acc[$p]['credit_count']++ : $acc[$p]['invoice_count']++;

            if ($withDetails) {
                $acc[$p]['invoices'][] = [
                    'id' => $invoice->id,
                    'number' => $invoice->number ?: '—',
                    'customer_name' => $invoice->customer_name,
                    'country' => $country,
                    'date_label' => $invoice->invoice_date->translatedFormat('j M'),
                    'is_credit' => (bool) $invoice->is_credit,
                    'base' => round($sign * (float) $invoice->subtotal, 2),
                    'vat' => round($sign * (float) $invoice->vat_total, 2),
                    'rubrieken' => array_keys($tags),
                ];
            }
        }

        // Voorbelasting (5b): btw op ingeboekte inkoopfacturen, op factuurdatum.
        $purchases = PurchaseInvoice::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->whereYear('invoice_date', $year)
            ->orderBy('invoice_date')->orderBy('id')
            ->get(['id', 'supplier_name', 'invoice_date', 'vat_total', 'total']);

        foreach ($purchases as $purchase) {
            $p = $this->periodIndex($type, $purchase->invoice_date);
            if (! isset($acc[$p])) {
                continue;
            }
            $acc[$p]['input_vat'] += (float) $purchase->vat_total;
            $acc[$p]['purchase_count']++;
            if ($withDetails) {
                $acc[$p]['purchases'][] = [
                    'id' => $purchase->id,
                    'supplier_name' => $purchase->supplier_name,
                    'date_label' => $purchase->invoice_date->translatedFormat('j M'),
                    'vat' => round((float) $purchase->vat_total, 2),
                    'total' => round((float) $purchase->total, 2),
                ];
            }
        }

        $filings = VatFiling::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('year', $year)
            ->where('period_type', $type)
            ->get()
            ->keyBy('period');

        $totals = [
            'base' => 0.0, 'vat' => 0.0, 'input_vat' => 0.0, 'balance' => 0.0,
            'invoice_count' => 0, 'credit_count' => 0, 'purchase_count' => 0,
        ];
        $result = [];
        foreach ($periods as $p => $meta) {
            $result[] = $this->buildPeriod($company, $meta, $acc[$p], $filings->get($p), $totals);
        }
        foreach (['base', 'vat', 'input_vat', 'balance'] as $k) {
            $totals[$k] = round($totals[$k], 2);
        }

        return ['period_type' => $type, 'periods' => $result, 'totals' => $totals];
    }

    /** Eén tijdvak uitwerken: rubrieken, afronding, status en betaalgegevens. */
    private function buildPeriod(Company $company, array $meta, array $acc, ?VatFiling $filing, array &$totals): array
    {
        $now = now();
        $manual = $filing?->manual ?? [];
        // Afronden in je voordeel: richting nul voor wat je moet betalen,
        // van nul af voor wat je terugkrijgt (voorbelasting).
        $down = fn (float $v): float => $v >= 0 ? floor($v + 1e-9) : -floor(-$v + 1e-9);
        $up = fn (float $v): float => $v >= 0 ? ceil($v - 1e-9) : -ceil(-$v - 1e-9);

        $rub = [];
        foreach (self::RUBRIEKEN as $key => $def) {
            $noVat = ! empty($def['no_vat']);
            [$base, $vat] = match ($def['source']) {
                'auto' => [$acc['auto'][$key]['base'], $noVat ? 0.0 : $acc['auto'][$key]['vat']],
                'manual' => [(float) ($manual[$key]['base'] ?? 0), $noVat ? 0.0 : (float) ($manual[$key]['vat'] ?? 0)],
                default => [null, 0.0],
            };
            $rub[$key] = [
                'key' => $key,
                'label' => $def['label'],
                'source' => $def['source'],
                'no_vat' => $noVat,
                'base' => $base === null ? null : round($base, 2),
                'vat' => round($vat, 2),
                'base_rounded' => $base === null ? null : $down($base),
                'vat_rounded' => $down($vat),
            ];
        }

        // 5b: voorbelasting uit de inkoop plus wat de ondernemer zelf aanvult.
        $inputAuto = (float) $acc['input_vat'];
        $inputExtra = (float) ($manual['5b']['vat'] ?? 0);
        $rub['5b']['auto'] = round($inputAuto, 2);
        $rub['5b']['extra'] = round($inputExtra, 2);
        $rub['5b']['vat'] = round($inputAuto + $inputExtra, 2);
        $rub['5b']['vat_rounded'] = $up($inputAuto + $inputExtra);

        // 5a: verschuldigde btw. Afgerond = som van de afgeronde rubrieken —
        // precies zoals het formulier het optelt.
        $owed = 0.0;
        $owedRounded = 0.0;
        foreach (self::OWED as $k) {
            $owed += $rub[$k]['vat'];
            $owedRounded += $rub[$k]['vat_rounded'];
        }
        $rub['5a']['vat'] = round($owed, 2);
        $rub['5a']['vat_rounded'] = $owedRounded;
        $rub['5c']['vat'] = round($owed - $rub['5b']['vat'], 2);
        $rub['5c']['vat_rounded'] = $owedRounded - $rub['5b']['vat_rounded'];

        $status = $now->lt($meta['start']) ? 'future' : ($now->lte($meta['end']) ? 'current' : 'closed');
        $filed = $filing?->filed_at !== null;
        $paid = $filing?->paid_at !== null;
        $due = $status === 'closed' && ! $filed && $now->lte($meta['deadline']);
        $balanceRounded = $rub['5c']['vat_rounded'];

        // Betalingskenmerk: handmatig ingevuld gaat voor; anders berekend uit
        // het omzetbelastingnummer (als dat is ingesteld).
        $reference = null;
        $referenceSource = null;
        if ($filing?->payment_reference) {
            $reference = VatPaymentReference::normalize($filing->payment_reference);
            $referenceSource = $reference ? 'manual' : null;
        }
        if (! $reference && $company->ob_number) {
            $reference = VatPaymentReference::forPeriod($company->ob_number, $meta['year'], $meta['type'], $meta['period']);
            $referenceSource = $reference ? 'auto' : null;
        }

        $turnoverBase = $rub['1a']['base'] + $rub['1b']['base'] + $rub['1e']['base'] + $rub['3a']['base'] + $rub['3b']['base'];
        $totals['base'] += $turnoverBase;
        $totals['vat'] += $rub['5a']['vat'];
        $totals['input_vat'] += $rub['5b']['vat'];
        $totals['balance'] += $rub['5c']['vat'];
        $totals['invoice_count'] += $acc['invoice_count'];
        $totals['credit_count'] += $acc['credit_count'];
        $totals['purchase_count'] += $acc['purchase_count'];

        $manualForm = [];
        foreach (self::MANUAL as $k) {
            $manualForm[$k] = [
                'base' => (float) ($manual[$k]['base'] ?? 0),
                'vat' => (float) ($manual[$k]['vat'] ?? 0),
            ];
        }

        return [
            'key' => "{$meta['year']}-{$meta['type']}-{$meta['period']}",
            'type' => $meta['type'],
            'period' => $meta['period'],
            'year' => $meta['year'],
            'label' => $meta['label'],
            'months' => $meta['months'],
            'status' => $status,
            'filed' => $filed,
            'paid' => $paid,
            'filed_at_label' => $filing?->filed_at?->translatedFormat('j M Y'),
            'paid_at_label' => $filing?->paid_at?->translatedFormat('j M Y'),
            'declaration_due' => $due,
            'unmarked' => $status === 'closed' && ! $filed && $now->gt($meta['deadline']),
            'days_left' => $due ? (int) $now->copy()->startOfDay()->diffInDays($meta['deadline']->copy()->startOfDay(), false) : null,
            'deadline' => $meta['deadline']->toDateString(),
            'deadline_label' => $meta['deadline']->translatedFormat('j F Y'),
            'rubrieken' => array_values($rub),
            'turnover_base' => round($turnoverBase, 2),
            'balance' => $rub['5c']['vat'],
            'balance_rounded' => $balanceRounded,
            'invoice_count' => $acc['invoice_count'],
            'credit_count' => $acc['credit_count'],
            'purchase_count' => $acc['purchase_count'],
            'invoices' => $acc['invoices'],
            'purchases' => $acc['purchases'],
            'manual' => $manualForm,
            'notes' => $filing?->notes,
            'payment' => [
                'amount' => $balanceRounded > 0 ? $balanceRounded : 0.0,
                'iban' => VatPaymentReference::IBAN,
                'bic' => VatPaymentReference::BIC,
                'beneficiary' => VatPaymentReference::BENEFICIARY,
                'reference' => $reference,
                'reference_formatted' => VatPaymentReference::format($reference),
                'reference_source' => $referenceSource,
            ],
        ];
    }

    /**
     * Wat vraagt nu aandacht? 'due' = afgesloten tijdvak waarvan de aangifte
     * nog niet is gemarkeerd en de deadline nog niet voorbij is (dit én vorig
     * jaar, want Q4 loopt tot eind januari); 'current' = het lopende tijdvak.
     */
    public function attention(Company $company): array
    {
        $year = now()->year;
        $due = null;
        $current = null;

        foreach ([$year - 1, $year] as $y) {
            $overview = $this->overview($company, $y, false);
            foreach ($overview['periods'] as $period) {
                if (! $due && $period['declaration_due']) {
                    $due = $period;
                }
                if ($y === $year && $period['status'] === 'current') {
                    $current = $period;
                }
            }
        }

        return ['due' => $due, 'current' => $current];
    }
}
