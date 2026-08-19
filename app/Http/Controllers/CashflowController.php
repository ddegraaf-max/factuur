<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\RecurringInvoice;
use App\Models\RecurringPurchase;
use App\Services\VatCalculator;
use Carbon\Carbon;
use Inertia\Inertia;

/**
 * Cashflow-prognose: wat komt er de komende maanden naar verwachting binnen
 * en wat gaat eruit? Opgebouwd uit gegevens die al in Easy staan:
 *
 *  IN  — openstaande facturen (op vervaldatum) + terugkerende facturen
 *        (verwachte ontvangst = factuurdatum + betaaltermijn).
 *  UIT — openstaande inkoopfacturen (op vervaldatum) + vaste lasten
 *        (op de boekingsdatum; die worden meestal geïncasseerd).
 *
 * Bewust een prognose, geen boekhouding: facturen in incasso tellen niet mee
 * (onzeker), en er is geen koppeling met het werkelijke banksaldo.
 */
class CashflowController extends Controller
{
    /** Aantal maanden vooruit (inclusief de huidige maand). */
    private const MONTHS = 4;

    public function index(VatCalculator $calculator)
    {
        $company = auth()->user()->company;
        $today = today();
        $windowEnd = $today->copy()->startOfMonth()->addMonths(self::MONTHS)->subDay()->endOfDay();

        // Maandemmers: huidige maand + drie vooruit.
        $months = [];
        for ($i = 0; $i < self::MONTHS; $i++) {
            $m = $today->copy()->startOfMonth()->addMonths($i);
            $months[$m->format('Y-m')] = [
                'key' => $m->format('Y-m'),
                'label' => ucfirst($m->translatedFormat('F Y')),
                'in_open' => 0.0, 'in_recurring' => 0.0,
                'out_open' => 0.0, 'out_recurring' => 0.0,
            ];
        }
        $overdue = ['in' => 0.0, 'out' => 0.0]; // al vervallen: direct opeisbaar
        $later = ['in' => 0.0, 'out' => 0.0];   // valt ná het venster

        $bucket = function (Carbon $date, string $side, string $field, float $amount) use (&$months, &$overdue, &$later, $today, $windowEnd) {
            if ($amount <= 0.009) {
                return;
            }
            if ($date->lt($today)) {
                $overdue[$side] += $amount;
            } elseif ($date->gt($windowEnd)) {
                $later[$side] += $amount;
            } else {
                $key = $date->format('Y-m');
                if (isset($months[$key])) {
                    $months[$key][$field] += $amount;
                }
            }
        };

        // ---- IN: openstaande verkoopfacturen (incasso is te onzeker) ----
        $openInvoices = Invoice::query()->regular()
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->get(['id', 'due_date', 'invoice_date', 'total', 'paid_total', 'status', 'is_credit']);
        foreach ($openInvoices as $invoice) {
            $due = $invoice->due_date ?? $invoice->invoice_date ?? $today;
            $bucket($due->copy(), 'in', 'in_open', (float) $invoice->remaining_amount);
        }
        $incassoTotal = (float) Invoice::query()->regular()->where('status', 'incasso')
            ->get(['total', 'paid_total'])->sum('remaining_amount');

        // ---- IN: terugkerende facturen (ontvangst = factuurdatum + termijn) ----
        $defaultTerms = (int) ($company->default_payment_terms ?? 14);
        foreach (RecurringInvoice::query()->where('active', true)->get() as $profile) {
            $amount = (float) $calculator->calculateInvoice($profile->lines ?? [], $company->price_mode === 'incl' ? 'incl' : 'excl')['total'];
            if ($amount <= 0.009) {
                continue;
            }
            $run = $profile->next_run_on?->copy();
            for ($guard = 0; $run && $run->lte($windowEnd) && $guard < 60; $guard++) {
                if ($profile->end_date && $run->gt($profile->end_date)) {
                    break;
                }
                $receipt = $run->copy()->addDays((int) ($profile->payment_terms ?? $defaultTerms));
                $bucket($receipt, 'in', 'in_recurring', $amount);
                $run = $profile->nextDateAfter($run);
            }
        }

        // ---- UIT: openstaande inkoopfacturen ----
        foreach (PurchaseInvoice::query()->open()->get() as $purchase) {
            $due = $purchase->due_date ?? $purchase->invoice_date ?? $today;
            $bucket($due->copy(), 'out', 'out_open', (float) $purchase->total);
        }

        // ---- UIT: vaste lasten (betaling op de boekingsdatum) ----
        foreach (RecurringPurchase::query()->where('active', true)->get() as $profile) {
            $amount = $profile->totalAmount();
            if ($amount <= 0.009) {
                continue;
            }
            $run = $profile->next_run_on?->copy();
            for ($guard = 0; $run && $run->lte($windowEnd) && $guard < 60; $guard++) {
                if ($profile->end_date && $run->gt($profile->end_date)) {
                    break;
                }
                $bucket($run->copy(), 'out', 'out_recurring', $amount);
                $run = $profile->nextDateAfter($run);
            }
        }

        // Netto per maand + cumulatief verloop (startend met wat al vervallen is).
        $cumulative = round($overdue['in'] - $overdue['out'], 2);
        $rows = [];
        foreach ($months as $m) {
            $in = round($m['in_open'] + $m['in_recurring'], 2);
            $out = round($m['out_open'] + $m['out_recurring'], 2);
            $cumulative = round($cumulative + $in - $out, 2);
            $rows[] = array_merge($m, [
                'in' => $in,
                'out' => $out,
                'net' => round($in - $out, 2),
                'cumulative' => $cumulative,
            ]);
        }

        $totalIn = round($overdue['in'] + collect($rows)->sum('in'), 2);
        $totalOut = round($overdue['out'] + collect($rows)->sum('out'), 2);

        return Inertia::render('Reports/Cashflow', [
            'months' => $rows,
            'overdue' => [
                'in' => round($overdue['in'], 2),
                'out' => round($overdue['out'], 2),
            ],
            'later' => [
                'in' => round($later['in'], 2),
                'out' => round($later['out'], 2),
            ],
            'totals' => [
                'in' => $totalIn,
                'out' => $totalOut,
                'net' => round($totalIn - $totalOut, 2),
            ],
            'incasso_total' => round($incassoTotal, 2),
        ]);
    }
}
