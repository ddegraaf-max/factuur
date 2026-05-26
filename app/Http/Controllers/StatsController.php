<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        // Distinct years available
        $allYears = Invoice::regular()
            ->whereNotIn('status', ['draft', 'cancelled'])
           ->selectRaw('DISTINCT EXTRACT(YEAR FROM invoice_date) AS yr')
            ->pluck('yr')
            ->map(fn ($y) => (int) $y)
            ->sortDesc()
            ->values()
            ->all();

        if (empty($allYears)) $allYears = [now()->year];

        // Pull finalized invoices for that year (incl. credit notes for subtraction)
        $invoices = Invoice::whereNotIn('status', ['draft', 'cancelled'])
            ->whereYear('invoice_date', $year)
            ->with('customer')
            ->get();

        $grouped = [];
        foreach ($invoices as $inv) {
            $cid = $inv->customer_id;
            if (! isset($grouped[$cid])) {
                $grouped[$cid] = [
                    'customer_id' => $cid,
                    'customer_name' => $inv->customer_name,
                    'customer_city' => $inv->customer?->city ?? '',
                    'invoice_count' => 0,
                    'credit_count' => 0,
                    'ex_vat' => 0,
                    'vat' => 0,
                    'inc_vat' => 0,
                ];
            }
            $sign = $inv->is_credit ? -1 : 1;
            if ($inv->is_credit) $grouped[$cid]['credit_count']++;
            else $grouped[$cid]['invoice_count']++;
            $grouped[$cid]['ex_vat'] += $sign * (float) $inv->subtotal;
            $grouped[$cid]['vat']     += $sign * (float) $inv->vat_total;
            $grouped[$cid]['inc_vat'] += $sign * (float) $inv->total;
        }

        $list = collect($grouped)
            ->map(function ($c) {
                $c['ex_vat'] = round($c['ex_vat'], 2);
                $c['vat'] = round($c['vat'], 2);
                $c['inc_vat'] = round($c['inc_vat'], 2);
                return $c;
            })
            ->sortByDesc('inc_vat')
            ->values();

        return Inertia::render('Stats/Index', [
            'year' => $year,
            'allYears' => $allYears,
            'list' => $list,
            'totals' => [
                'customers' => $list->count(),
                'ex_vat' => round($list->sum('ex_vat'), 2),
                'vat' => round($list->sum('vat'), 2),
                'inc_vat' => round($list->sum('inc_vat'), 2),
                'invoice_count' => $list->sum('invoice_count'),
                'credit_count' => $list->sum('credit_count'),
            ],
        ]);
    }
}
