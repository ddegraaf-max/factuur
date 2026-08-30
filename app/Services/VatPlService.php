<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\VatFiling;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Rozliczenie VAT (Polen): de kwoty voor JPK_V7M (maand) of JPK_V7K
 * (kwartaal) per okres rozliczeniowy.
 *
 *  - VAT należny: verkoopfactuurregels per stawka (23 / 8 / 5 / 0) op
 *    factuurdatum; creditnota's (faktury korygujące) tellen negatief mee.
 *    Concepten en geannuleerde facturen niet.
 *  - VAT naliczony: btw op ingeboekte inkoopfacturen (zakupy) in het okres.
 *  - Saldo = należny − naliczony: positief → do zapłaty, negatief → do
 *    zwrotu / do przeniesienia na następny okres.
 *  - Termijn: 25e van de maand na het okres (kwartaal: 25e na het kwartaal);
 *    valt die in het weekend, dan de eerstvolgende maandag.
 *  - Afronding zoals in het deklaracja-deel van JPK_V7: hele złoty, eindjes
 *    onder 50 gr vervallen, 50 gr en meer gaan omhoog (art. 63 Ordynacji
 *    podatkowej). Exacte bedragen én afgeronde bedragen worden beide gegeven.
 *
 * Een aparte stawka 'zw' (zwolnione) is er niet: de administratie kent geen
 * vrijstellingsvlag per regel of per bedrijf, dus alle 0%-regels staan op één
 * 0%-rij. Regels met een tarief buiten 23/8/5/0 (bijv. geïmporteerde
 * Nederlandse data) komen apart in 'other' terecht, zodat er niets verdwijnt.
 *
 * De status per okres (złożona / zapłacona) gebruikt hetzelfde VatFiling-model
 * als de Nederlandse aangifte (period_type 'month' of 'quarter').
 */
class VatPlService
{
    public const PERIOD_TYPES = ['month', 'quarter'];

    /** Stawki in de volgorde van het formulier. */
    public const RATES = [23, 8, 5, 0];

    private const ROMAN = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];

    public static function periodType(?Company $company): string
    {
        $type = $company?->vat_period;

        return in_array($type, self::PERIOD_TYPES, true) ? $type : 'month';
    }

    /** Aantal okresy in een jaar: 12 maanden of 4 kwartalen. */
    public static function periodCount(string $type): int
    {
        return $type === 'quarter' ? 4 : 12;
    }

    /** Formuliernaam: JPK_V7M (maand) of JPK_V7K (kwartaal). */
    public static function formName(string $type): string
    {
        return $type === 'quarter' ? 'JPK_V7K' : 'JPK_V7M';
    }

    /** In welk okres (1..n) valt deze datum? */
    public function periodIndex(string $type, Carbon $date): int
    {
        return $type === 'quarter' ? (int) ceil($date->month / 3) : $date->month;
    }

    /**
     * Okresy van een jaar: label (Pools), begin, einde, termijn en status.
     * Bijv. 'wrzesień 2026' of 'III kwartał 2026'.
     */
    public function periods(string $type, int $year): array
    {
        $this->assertType($type);
        $now = now();
        $list = [];

        for ($p = 1; $p <= self::periodCount($type); $p++) {
            if ($type === 'quarter') {
                $start = Carbon::create($year, ($p - 1) * 3 + 1, 1)->startOfDay();
                $end = $start->copy()->addMonths(2)->endOfMonth()->endOfDay();
                $label = self::ROMAN[$p] . " kwartał {$year}";
                $short = self::ROMAN[$p] . ' kw.';
                // Symbol okresu op de belastingoverschrijving: 26K03.
                $symbol = sprintf('%02dK%02d', $year % 100, $p);
            } else {
                $start = Carbon::create($year, $p, 1)->startOfDay();
                $end = $start->copy()->endOfMonth()->endOfDay();
                $label = $start->copy()->locale('pl')->translatedFormat('F Y');
                $short = $start->copy()->locale('pl')->translatedFormat('F');
                $symbol = sprintf('%02dM%02d', $year % 100, $p);
            }

            $list[$p] = [
                'type' => $type,
                'period' => $p,
                'year' => $year,
                'label' => $label,
                'short_label' => $short,
                'payment_symbol' => $symbol,
                'start' => $start,
                'end' => $end,
                'due_date' => $this->dueDate($end),
                'status' => $now->lt($start) ? 'future' : ($now->lte($end) ? 'current' : 'closed'),
            ];
        }

        return $list;
    }

    /**
     * Welk okres open je standaard? Het laatst afgesloten okres van dit jaar
     * (dat is wat je nu aangeeft), anders het eerste; vorige jaren het laatste.
     */
    public function defaultPeriod(string $type, int $year): int
    {
        $count = self::periodCount($type);
        $now = now();

        if ($year < $now->year) {
            return $count;
        }
        if ($year > $now->year) {
            return 1;
        }

        return max(1, $this->periodIndex($type, $now) - 1);
    }

    /** Okresy van een jaar voor de keuzelijst, inclusief złożona/zapłacona. */
    public function periodList(Company $company, string $type, int $year): array
    {
        $filings = $this->filings($company, $type, $year);

        return array_values(array_map(fn (array $meta) => [
            'period' => $meta['period'],
            'label' => $meta['label'],
            'short_label' => $meta['short_label'],
            'status' => $meta['status'],
            'due_date' => $meta['due_date']->toDateString(),
            'filed' => $filings->get($meta['period'])?->filed_at !== null,
            'paid' => $filings->get($meta['period'])?->paid_at !== null,
        ], $this->periods($type, $year)));
    }

    /**
     * Alle kwoty van één okres voor JPK_V7:
     *   sales     per stawka ['net', 'vat', 'gross', …_rounded]
     *   purchases ['net', 'vat', 'gross', 'count', …_rounded]
     *   output_vat / input_vat / balance (+ _rounded)
     *   due_date, period_label, filing.
     */
    public function summary(Company $company, string $periodType, int $year, int $period): array
    {
        $this->assertType($periodType);
        $periods = $this->periods($periodType, $year);
        if (! isset($periods[$period])) {
            throw new InvalidArgumentException("Okres {$period} bestaat niet voor {$periodType} {$year}.");
        }
        $meta = $periods[$period];
        $now = now();

        $sales = [];
        foreach (self::RATES as $rate) {
            $sales[(string) $rate] = ['rate' => $rate, 'label' => "{$rate}%", 'net' => 0.0, 'vat' => 0.0];
        }
        $sales['other'] = ['rate' => null, 'label' => 'inne stawki', 'net' => 0.0, 'vat' => 0.0];

        // Expliciet op company filteren: de auth-scope grijpt niet vanuit de console.
        $invoices = Invoice::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('invoice_date', [$meta['start']->toDateString(), $meta['end']->toDateString()])
            ->with('lines')
            ->orderBy('invoice_date')->orderBy('id')
            ->get();

        $invoiceCount = 0;
        $creditCount = 0;
        $documents = [];
        foreach ($invoices as $invoice) {
            $sign = $invoice->is_credit ? -1 : 1;
            $rates = [];
            foreach ($invoice->lines as $line) {
                $rate = (int) round((float) $line->vat_rate);
                $key = in_array($rate, self::RATES, true) ? (string) $rate : 'other';
                $sales[$key]['net'] += $sign * (float) $line->line_subtotal;
                $sales[$key]['vat'] += $sign * (float) $line->line_vat;
                $rates[$key] = true;
            }
            $invoice->is_credit ? $creditCount++ : $invoiceCount++;

            $documents[] = [
                'id' => $invoice->id,
                'number' => $invoice->number ?: '—',
                'customer_name' => $invoice->customer_name,
                'country' => strtoupper(trim((string) $invoice->customer_country)) ?: 'PL',
                'date' => $invoice->invoice_date->toDateString(),
                'date_label' => $invoice->invoice_date->format('d.m.Y'),
                'is_credit' => (bool) $invoice->is_credit,
                'net' => round($sign * (float) $invoice->subtotal, 2),
                'vat' => round($sign * (float) $invoice->vat_total, 2),
                'gross' => round($sign * (float) $invoice->total, 2),
                'rates' => array_keys($rates),
            ];
        }

        // VAT naliczony: btw op ingeboekte inkoopfacturen, op factuurdatum.
        $purchaseRows = PurchaseInvoice::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->whereBetween('invoice_date', [$meta['start']->toDateString(), $meta['end']->toDateString()])
            ->orderBy('invoice_date')->orderBy('id')
            ->get(['id', 'supplier_name', 'supplier_reference', 'invoice_date', 'subtotal', 'vat_total', 'total']);

        $purchases = ['net' => 0.0, 'vat' => 0.0, 'gross' => 0.0, 'count' => 0];
        $purchaseDocs = [];
        foreach ($purchaseRows as $purchase) {
            $purchases['net'] += (float) $purchase->subtotal;
            $purchases['vat'] += (float) $purchase->vat_total;
            $purchases['gross'] += (float) $purchase->total;
            $purchases['count']++;
            $purchaseDocs[] = [
                'id' => $purchase->id,
                'supplier_name' => $purchase->supplier_name,
                'reference' => $purchase->supplier_reference,
                'date' => $purchase->invoice_date->toDateString(),
                'date_label' => $purchase->invoice_date->format('d.m.Y'),
                'net' => round((float) $purchase->subtotal, 2),
                'vat' => round((float) $purchase->vat_total, 2),
                'gross' => round((float) $purchase->total, 2),
            ];
        }

        // Afronden per veld, precies zoals het deklaracja-deel van JPK_V7 het
        // optelt: należny = som van de afgeronde stawki.
        $outputVat = 0.0;
        $outputVatRounded = 0;
        $salesNet = 0.0;
        $salesNetRounded = 0;
        foreach ($sales as $key => &$row) {
            $row['net'] = round($row['net'], 2);
            $row['vat'] = round($row['vat'], 2);
            $row['gross'] = round($row['net'] + $row['vat'], 2);
            $row['net_rounded'] = self::zl($row['net']);
            $row['vat_rounded'] = self::zl($row['vat']);
            $row['gross_rounded'] = $row['net_rounded'] + $row['vat_rounded'];
            $row['empty'] = abs($row['net']) < 0.005 && abs($row['vat']) < 0.005;
            $outputVat += $row['vat'];
            $outputVatRounded += $row['vat_rounded'];
            $salesNet += $row['net'];
            $salesNetRounded += $row['net_rounded'];
        }
        unset($row);

        foreach (['net', 'vat', 'gross'] as $k) {
            $purchases[$k] = round($purchases[$k], 2);
            $purchases[$k . '_rounded'] = self::zl($purchases[$k]);
        }

        $inputVat = $purchases['vat'];
        $balance = round($outputVat - $inputVat, 2);
        $balanceRounded = $outputVatRounded - $purchases['vat_rounded'];

        $filing = $this->filings($company, $periodType, $year)->get($period);
        $filed = $filing?->filed_at !== null;
        $paid = $filing?->paid_at !== null;
        $due = $meta['status'] === 'closed' && ! $filed && $now->lte($meta['due_date']);

        return [
            'key' => "{$year}-{$periodType}-{$period}",
            'period_type' => $periodType,
            'year' => $year,
            'period' => $period,
            'period_label' => $meta['label'],
            'form' => self::formName($periodType),
            'payment_symbol' => $meta['payment_symbol'],
            'start' => $meta['start']->toDateString(),
            'end' => $meta['end']->toDateString(),
            'status' => $meta['status'],
            'due_date' => $meta['due_date']->toDateString(),
            'due_date_label' => $meta['due_date']->format('d.m.Y'),
            'due_date_long' => $meta['due_date']->copy()->locale('pl')->translatedFormat('j F Y'),
            'declaration_due' => $due,
            'days_left' => $due ? (int) $now->copy()->startOfDay()->diffInDays($meta['due_date']->copy()->startOfDay(), false) : null,
            'overdue' => $meta['status'] === 'closed' && ! $filed && $now->gt($meta['due_date']),
            'sales' => $sales,
            'sales_net' => round($salesNet, 2),
            'sales_net_rounded' => $salesNetRounded,
            'purchases' => $purchases,
            'output_vat' => round($outputVat, 2),
            'output_vat_rounded' => $outputVatRounded,
            'input_vat' => round($inputVat, 2),
            'input_vat_rounded' => $purchases['vat_rounded'],
            'balance' => $balance,
            'balance_rounded' => $balanceRounded,
            'balance_kind' => $balanceRounded > 0 ? 'to_pay' : ($balanceRounded < 0 ? 'refund' : 'zero'),
            'invoice_count' => $invoiceCount,
            'credit_count' => $creditCount,
            'purchase_count' => $purchases['count'],
            'documents' => ['sales' => $documents, 'purchases' => $purchaseDocs],
            'filing' => [
                'filed' => $filed,
                'paid' => $paid,
                'filed_at_label' => $filing?->filed_at?->format('d.m.Y'),
                'paid_at_label' => $filing?->paid_at?->format('d.m.Y'),
                'notes' => $filing?->notes,
            ],
        ];
    }

    /**
     * Hele złoty zoals in de deklaracja: eindjes onder 50 gr vervallen,
     * 50 gr en meer gaan omhoog; negatieve bedragen op dezelfde manier.
     */
    public static function zl(float $amount): int
    {
        $abs = floor(abs($amount) + 0.5 + 1e-9);

        return (int) ($amount < 0 ? -$abs : $abs);
    }

    /** 25e van de maand na het okres; in het weekend → eerstvolgende maandag. */
    private function dueDate(Carbon $periodEnd): Carbon
    {
        $due = $periodEnd->copy()->addDay()->startOfMonth()->setDay(25)->endOfDay();
        while ($due->isWeekend()) {
            $due->addDay();
        }

        return $due;
    }

    private function filings(Company $company, string $type, int $year): \Illuminate\Support\Collection
    {
        return VatFiling::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('year', $year)
            ->where('period_type', $type)
            ->get()
            ->keyBy('period');
    }

    private function assertType(string $type): void
    {
        if (! in_array($type, self::PERIOD_TYPES, true)) {
            throw new InvalidArgumentException("Onbekend okres-type: {$type}");
        }
    }
}
