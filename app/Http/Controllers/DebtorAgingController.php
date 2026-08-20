<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Inertia\Inertia;

/**
 * Ouderdomsanalyse debiteuren: wie staat er hoe lang open? Openstaande
 * facturen per klant in leeftijdsemmers (nog niet vervallen, 1-30, 31-60,
 * 61-90 en 90+ dagen over de vervaldatum) — de klassieke "aging"-lijst
 * die boekhouders vragen, en de logische buur van de cashflow-prognose.
 */
class DebtorAgingController extends Controller
{
    public function index()
    {
        $today = today();

        $invoices = Invoice::query()->regular()
            ->whereIn('status', ['sent', 'partial', 'overdue', 'incasso'])
            ->orderBy('due_date')
            ->get(['id', 'number', 'customer_id', 'customer_name', 'status', 'invoice_date', 'due_date', 'total', 'paid_total']);

        $bucketOf = function (Invoice $invoice) use ($today): string {
            $due = $invoice->due_date ?? $invoice->invoice_date;
            if (! $due || ! $due->isBefore($today)) {
                return 'current';
            }
            $days = (int) $due->diffInDays($today);

            return match (true) {
                $days <= 30 => 'b30',
                $days <= 60 => 'b60',
                $days <= 90 => 'b90',
                default => 'b90plus',
            };
        };

        $emptyRow = fn () => ['current' => 0.0, 'b30' => 0.0, 'b60' => 0.0, 'b90' => 0.0, 'b90plus' => 0.0, 'total' => 0.0, 'oldest_days' => 0, 'count' => 0, 'incasso' => 0];

        $customers = [];
        $totals = $emptyRow();
        $oldest = [];

        foreach ($invoices as $invoice) {
            $open = round((float) $invoice->remaining_amount, 2);
            if ($open <= 0.009) {
                continue;
            }

            $key = $invoice->customer_id ?? ('naam:' . $invoice->customer_name);
            if (! isset($customers[$key])) {
                $customers[$key] = array_merge($emptyRow(), [
                    'customer_id' => $invoice->customer_id,
                    'name' => $invoice->customer_name,
                ]);
            }

            $bucket = $bucketOf($invoice);
            $due = $invoice->due_date ?? $invoice->invoice_date;
            $days = ($due && $due->isBefore($today)) ? (int) $due->diffInDays($today) : 0;

            $customers[$key][$bucket] += $open;
            $customers[$key]['total'] += $open;
            $customers[$key]['count']++;
            $customers[$key]['oldest_days'] = max($customers[$key]['oldest_days'], $days);
            $customers[$key]['incasso'] += $invoice->status === 'incasso' ? 1 : 0;

            $totals[$bucket] += $open;
            $totals['total'] += $open;
            $totals['count']++;

            if ($days > 0) {
                $oldest[] = [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'customer_name' => $invoice->customer_name,
                    'status' => $invoice->status,
                    'due_label' => $due->translatedFormat('j M Y'),
                    'days' => $days,
                    'open' => $open,
                ];
            }
        }

        $rows = collect($customers)
            ->map(fn ($row) => array_map(fn ($v) => is_float($v) ? round($v, 2) : $v, $row))
            ->sortByDesc('total')
            ->values();

        $overdueTotal = round($totals['b30'] + $totals['b60'] + $totals['b90'] + $totals['b90plus'], 2);

        return Inertia::render('Reports/Debiteuren', [
            'rows' => $rows,
            'totals' => array_map(fn ($v) => is_float($v) ? round($v, 2) : $v, $totals),
            'overdue_total' => $overdueTotal,
            'oldest' => collect($oldest)->sortByDesc('days')->take(10)->values(),
        ]);
    }
}
