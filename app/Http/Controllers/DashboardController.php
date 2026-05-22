<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // First, mark overdue
        Invoice::where('status', 'sent')
            ->whereDate('due_date', '<', now())
            ->update(['status' => 'overdue']);

        $outstanding = Invoice::open()->sum(DB::raw('total - paid_total'));
        $outstandingCount = Invoice::open()->count();

        $overdue = Invoice::where('status', 'overdue')->sum(DB::raw('total - paid_total'));
        $overdueCount = Invoice::where('status', 'overdue')->count();

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $monthRevenue = Invoice::whereIn('status', ['sent', 'partial', 'paid', 'overdue'])
            ->whereBetween('invoice_date', [$startOfMonth, $endOfMonth])
            ->sum('subtotal');

        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth = now()->subMonth()->endOfMonth();
        $lastMonthRevenue = Invoice::whereIn('status', ['sent', 'partial', 'paid', 'overdue'])
            ->whereBetween('invoice_date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('subtotal');

        $monthChange = $lastMonthRevenue > 0
            ? round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        // VAT to pay this quarter
        $quarterStart = now()->firstOfQuarter();
        $quarterEnd = now()->lastOfQuarter();
        $vatToPay = Invoice::whereIn('status', ['sent', 'partial', 'paid', 'overdue'])
            ->whereBetween('invoice_date', [$quarterStart, $quarterEnd])
            ->sum('vat_total');

        $quarterNumber = ceil(now()->month / 3);
        $quarterDeadline = $quarterEnd->copy()->addMonth()->endOfMonth();

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

        // Revenue per month — last 12 months
        $monthly = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $value = Invoice::whereIn('status', ['sent', 'partial', 'paid', 'overdue'])
                ->whereYear('invoice_date', $m->year)
                ->whereMonth('invoice_date', $m->month)
                ->sum('subtotal');
            $monthly[] = [
                'month' => $m->translatedFormat('M Y'),
                'value' => (float) $value,
            ];
        }

        return Inertia::render('Dashboard', [
            'kpis' => [
                'outstanding' => (float) $outstanding,
                'outstanding_count' => $outstandingCount,
                'overdue' => (float) $overdue,
                'overdue_count' => $overdueCount,
                'month_revenue' => (float) $monthRevenue,
                'month_change' => $monthChange,
                'vat_to_pay' => (float) $vatToPay,
                'quarter_number' => $quarterNumber,
                'quarter_deadline' => $quarterDeadline->translatedFormat('j M Y'),
            ],
            'recent_invoices' => $recentInvoices,
            'monthly_revenue' => $monthly,
        ]);
    }
}
