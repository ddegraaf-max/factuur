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

        // Beschikbare jaren (portable: geen EXTRACT — werkt ook op SQLite).
        $allYears = Invoice::regular()
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->pluck('invoice_date')
            ->map(fn ($d) => (int) substr((string) $d, 0, 4))
            ->unique()
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

        // Winstgevendheid: bestede uren en gereden kilometers per klant in
        // hetzelfde jaar — samen met de omzet geeft dat het effectieve
        // uurtarief (omzet excl. btw ÷ uren): wat een klant écht oplevert.
        $from = \Carbon\Carbon::create($year, 1, 1)->toDateString();
        $to = \Carbon\Carbon::create($year, 12, 31)->toDateString();

        $minutesByCustomer = \App\Models\TimeEntry::query()
            ->whereNotNull('customer_id')
            ->whereNull('timer_started_at')
            ->whereBetween('work_date', [$from, $to])
            ->selectRaw('customer_id, SUM(minutes) AS minutes_sum')
            ->groupBy('customer_id')
            ->pluck('minutes_sum', 'customer_id');

        $kmByCustomer = \App\Models\Trip::query()
            ->whereNotNull('customer_id')
            ->whereBetween('trip_date', [$from, $to])
            ->selectRaw('customer_id, SUM(kilometers) AS km_sum')
            ->groupBy('customer_id')
            ->pluck('km_sum', 'customer_id');

        // Klanten met uren maar (nog) zonder omzet horen er ook bij: die
        // kosten tijd — juist die wil je zien in een winstgevendheidslijst.
        $missing = $minutesByCustomer->keys()->diff(collect($grouped)->pluck('customer_id'));
        if ($missing->isNotEmpty()) {
            foreach (Customer::whereIn('id', $missing)->get(['id', 'name', 'city']) as $customer) {
                $grouped[$customer->id] = [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_city' => $customer->city ?? '',
                    'invoice_count' => 0,
                    'credit_count' => 0,
                    'ex_vat' => 0,
                    'vat' => 0,
                    'inc_vat' => 0,
                ];
            }
        }

        $list = collect($grouped)
            ->map(function ($c) use ($minutesByCustomer, $kmByCustomer) {
                $c['ex_vat'] = round($c['ex_vat'], 2);
                $c['vat'] = round($c['vat'], 2);
                $c['inc_vat'] = round($c['inc_vat'], 2);
                $minutes = (int) ($minutesByCustomer[$c['customer_id']] ?? 0);
                $c['hours'] = round($minutes / 60, 1);
                $c['km'] = round((float) ($kmByCustomer[$c['customer_id']] ?? 0), 1);
                $c['effective_rate'] = $c['hours'] > 0 ? round($c['ex_vat'] / $c['hours'], 2) : null;
                return $c;
            })
            ->sortByDesc('inc_vat')
            ->values();

        $withHours = $list->filter(fn ($c) => $c['hours'] > 0);
        $totalHours = round($list->sum('hours'), 1);

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
                'hours' => $totalHours,
                'effective_rate' => $totalHours > 0 ? round($withHours->sum('ex_vat') / $totalHours, 2) : null,
            ],
        ]);
    }
}
