<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\Quote;
use App\Services\VatService;
use App\Support\Sql;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(VatService $vat): Response
    {
        // First, mark overdue
        Invoice::where('status', 'sent')
            ->whereDate('due_date', '<', now())
            ->update(['status' => 'overdue']);

        $outstanding = Invoice::open()->sum(DB::raw('total - paid_total'));
        $outstandingCount = Invoice::open()->count();

        $overdue = Invoice::where('status', 'overdue')->sum(DB::raw('total - paid_total'));
        $overdueCount = Invoice::where('status', 'overdue')->count();

        // Omzet met creditnota's als aftrekpost (anders tellen ze dubbel op).
        $signedRevenue = 'COALESCE(SUM(CASE WHEN is_credit THEN -subtotal ELSE subtotal END), 0) AS rev';

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $monthRevenue = (float) Invoice::whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('invoice_date', [$startOfMonth, $endOfMonth])
            ->selectRaw($signedRevenue)->value('rev');

        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth = now()->subMonth()->endOfMonth();
        $lastMonthRevenue = (float) Invoice::whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('invoice_date', [$startOfLastMonth, $endOfLastMonth])
            ->selectRaw($signedRevenue)->value('rev');

        $monthChange = $lastMonthRevenue > 0
            ? round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        // Btw: het lopende tijdvak per saldo (btw over omzet min voorbelasting)
        // en het afgesloten tijdvak dat nu aangifte vraagt.
        $vatAttention = $vat->attention(auth()->user()->company);
        $vatCurrent = $vatAttention['current'];
        $vatDue = $vatAttention['due'];
        $vatToPay = $vatCurrent['balance'] ?? 0.0;
        $vatPeriodLabel = $vatCurrent['label'] ?? '';
        $quarterNumber = ceil(now()->month / 3);
        $quarterDeadline = $vatCurrent ? Carbon::parse($vatCurrent['deadline']) : now()->lastOfQuarter()->addMonth()->endOfMonth();

        // Recent invoices
        $recentInvoices = Invoice::with('customer')
            ->latest('invoice_date')
            ->limit(7)
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'number' => $i->number ?? '— concept —',
                'customer_name' => $i->customer_name,
                'invoice_date' => $i->invoice_date->format('d M Y'),
                'status' => $i->status,
                'total' => (float) $i->total,
            ]);

        // Offertes: wat wacht op een reactie, wat is geaccepteerd maar nog
        // niet gefactureerd, en hoe vaak zegt een klant ja.
        $openQuotes = Quote::where('status', 'sent')->get(['id', 'status', 'total', 'valid_until']);
        $toInvoice = Quote::where('status', 'accepted')->whereNull('converted_invoice_id')->get(['id', 'total']);
        $decidedThisYear = Quote::whereIn('status', ['accepted', 'rejected', 'expired'])
            ->whereYear('quote_date', now()->year)
            ->get(['id', 'status', 'total']);
        $acceptedThisYear = $decidedThisYear->where('status', 'accepted');

        $quotes = [
            'open_count' => $openQuotes->count(),
            'open_total' => round((float) $openQuotes->sum('total'), 2),
            'expiring_count' => $openQuotes->filter(fn ($q) => ! $q->is_expired && $q->days_left <= 7)->count(),
            'expired_count' => $openQuotes->filter(fn ($q) => $q->is_expired)->count(),
            'draft_count' => Quote::where('status', 'draft')->count(),
            'to_invoice_count' => $toInvoice->count(),
            'to_invoice_total' => round((float) $toInvoice->sum('total'), 2),
            'accepted_year_count' => $acceptedThisYear->count(),
            'accepted_year_total' => round((float) $acceptedThisYear->sum('total'), 2),
            'decided_year_count' => $decidedThisYear->count(),
            'acceptance_rate' => $decidedThisYear->count() > 0
                ? (int) round($acceptedThisYear->count() / $decidedThisYear->count() * 100)
                : null,
            'recent' => Quote::latest('quote_date')->latest('id')->limit(6)->get()->map(fn ($q) => [
                'id' => $q->id,
                'number' => $q->number ?? '— concept —',
                'customer_name' => $q->customer_name,
                'quote_date' => $q->quote_date->format('d M Y'),
                'status' => $q->status,
                'status_label' => $q->status_label,
                'is_expired' => $q->is_expired,
                'days_left' => $q->status === 'sent' ? $q->days_left : null,
                'to_invoice' => $q->status === 'accepted' && ! $q->converted_invoice_id,
                'total' => (float) $q->total,
            ]),
        ];

        // Resultaat per maand: omzet, inkoop en winst voor dit én vorig jaar,
        // zodat de grafiek winst/verlies en de groei ten opzichte van vorig
        // jaar kan laten zien.
        $year = now()->year;
        $rev = $this->monthlyRevenue($year);
        $revPrev = $this->monthlyRevenue($year - 1);
        $costs = $this->monthlyCosts($year);
        $costsPrev = $this->monthlyCosts($year - 1);

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = [
                'label' => Carbon::create($year, $m, 1)->translatedFormat('M'),
                'revenue' => $rev[$m] ?? 0.0,
                'costs' => $costs[$m] ?? 0.0,
                'profit' => round(($rev[$m] ?? 0.0) - ($costs[$m] ?? 0.0), 2),
                'prev_revenue' => $revPrev[$m] ?? 0.0,
                'prev_profit' => round(($revPrev[$m] ?? 0.0) - ($costsPrev[$m] ?? 0.0), 2),
            ];
        }

        // Jaartotalen t/m de huidige maand, vergeleken met dezelfde periode vorig jaar.
        $upTo = now()->month;
        $sumUpTo = fn (array $series) => round(array_sum(array_intersect_key($series, array_flip(range(1, $upTo)))), 2);
        $revYtd = $sumUpTo($rev);
        $revPrevYtd = $sumUpTo($revPrev);
        $costsYtd = $sumUpTo($costs);
        $profitYtd = round($revYtd - $costsYtd, 2);
        $profitPrevYtd = round($revPrevYtd - $sumUpTo($costsPrev), 2);

        $growth = function (float $now, float $before): ?float {
            if (abs($before) < 0.01) return null;
            return round(($now - $before) / abs($before) * 100, 1);
        };

        return Inertia::render('Dashboard', [
            'kpis' => [
                'outstanding' => (float) $outstanding,
                'outstanding_count' => $outstandingCount,
                'overdue' => (float) $overdue,
                'overdue_count' => $overdueCount,
                'month_revenue' => (float) $monthRevenue,
                'month_change' => $monthChange,
                'vat_to_pay' => (float) $vatToPay,
                'vat_period_label' => $vatPeriodLabel,
                'quarter_number' => $quarterNumber,
                'quarter_deadline' => $quarterDeadline->translatedFormat('j M Y'),
            ],
            // Aangifte die nu open staat (afgesloten tijdvak, deadline nog niet voorbij).
            'vat_due' => $vatDue ? [
                'label' => $vatDue['label'],
                'year' => $vatDue['year'],
                'deadline_label' => $vatDue['deadline_label'],
                'days_left' => $vatDue['days_left'],
                'balance_rounded' => $vatDue['balance_rounded'],
            ] : null,
            'recent_invoices' => $recentInvoices,
            'quotes' => $quotes,
            'result_chart' => [
                'year' => $year,
                'prev_year' => $year - 1,
                'months' => $months,
                'has_prev' => array_sum($revPrev) != 0.0 || array_sum($costsPrev) != 0.0,
                'has_costs' => array_sum($costs) != 0.0 || array_sum($costsPrev) != 0.0,
                'totals' => [
                    'revenue' => $revYtd,
                    'costs' => $costsYtd,
                    'profit' => $profitYtd,
                    'revenue_growth' => $growth($revYtd, $revPrevYtd),
                    'profit_growth' => $growth($profitYtd, $profitPrevYtd),
                    'period_label' => 'jan t/m ' . now()->translatedFormat('M'),
                ],
            ],
        ]);
    }

    /** Omzet per maand (excl. btw, creditnota's negatief) voor één kalenderjaar. */
    private function monthlyRevenue(int $year): array
    {
        return Invoice::whereNotIn('status', ['draft', 'cancelled'])
            ->whereYear('invoice_date', $year)
            ->selectRaw(Sql::month('invoice_date') . ' AS m, SUM(CASE WHEN is_credit THEN -subtotal ELSE subtotal END) AS total')
            ->groupBy('m')
            ->pluck('total', 'm')
            ->mapWithKeys(fn ($v, $k) => [(int) $k => (float) $v])
            ->all();
    }

    /** Inkoopkosten per maand (excl. btw) voor één kalenderjaar. */
    private function monthlyCosts(int $year): array
    {
        return PurchaseInvoice::whereYear('invoice_date', $year)
            ->selectRaw(Sql::month('invoice_date') . ' AS m, SUM(subtotal) AS total')
            ->groupBy('m')
            ->pluck('total', 'm')
            ->mapWithKeys(fn ($v, $k) => [(int) $k => (float) $v])
            ->all();
    }
}
