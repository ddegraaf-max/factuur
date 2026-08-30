<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;

class EasyInsightsService
{
    public function gather(): array
    {
        $insights = [];

        $overdue = Invoice::regular()->where('status', 'overdue')->get();
        if ($overdue->count() > 0) {
            $total = $overdue->sum(fn ($i) => (float) $i->total - (float) $i->paid_total);
            $insights[] = [
                'severity' => 'danger',
                'title' => trans_choice(':count achterstallige factuur|:count achterstallige facturen', $overdue->count(), ['count' => $overdue->count()]),
                // Nadruk met **sterretjes**, niet met HTML: de frontend zet dit
                // veilig om naar vet zonder de tekst als opmaak te vertrouwen.
                'detail' => __('Totaal openstaand: **:amount**', ['amount' => money($total)]),
            ];
        }

        $incasso = Invoice::regular()->where('status', 'incasso')->count();
        if ($incasso > 0) {
            $insights[] = [
                'severity' => 'warning',
                'title' => trans_choice(':count dossier bij :partner|:count dossiers bij :partner', $incasso, ['count' => $incasso, 'partner' => \App\Support\Market::incasso('partner_name')]),
                'detail' => __('De deurwaarder behandelt deze namens jou.'),
            ];
        }

        $now = now();
        $qNum = (int) ceil($now->month / 3);
        $qEnd = Carbon::create($now->year, $qNum * 3, 1)->endOfMonth();
        $deadline = $qEnd->copy()->addMonth()->endOfMonth();
        $days = $now->diffInDays($deadline, false);
        if ($days > 0 && $days <= 30) {
            $insights[] = [
                'severity' => 'info',
                'title' => __('BTW Q:quarter-aangifte over :days dagen', ['quarter' => $qNum, 'days' => $days]),
                'detail' => __('Deadline :date.', ['date' => $deadline->isoFormat('D MMMM')]),
            ];
        }

        $incompleteB2B = Customer::where('type', 'business')->whereNull('kvk_number')->count();
        if ($incompleteB2B > 0) {
            $registry = (string) \App\Support\Market::get('registry.short', 'KVK');
            $insights[] = [
                'severity' => 'warning',
                'title' => trans_choice(':count zakelijke klant zonder :registry|:count zakelijke klanten zonder :registry', $incompleteB2B, ['count' => $incompleteB2B, 'registry' => $registry]),
                'detail' => __('Voeg het :label toe voor je administratie.', ['label' => \App\Support\Market::get('registry.label', 'KVK-nummer')]),
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'severity' => 'success',
                'title' => __('Alles ziet er goed uit'),
                'detail' => __('Geen achterstallige facturen, geen openstaande dossiers.'),
            ];
        }

        return $insights;
    }

    public function data(): array
    {
        $now = now();
        $qNum = (int) ceil($now->month / 3);
        $qStart = Carbon::create($now->year, ($qNum - 1) * 3 + 1, 1);
        $qEnd = Carbon::create($now->year, $qNum * 3, 1)->endOfMonth();
        $deadline = $qEnd->copy()->addMonth()->endOfMonth();

        $vatThisQuarter = (float) Invoice::whereNotIn('status', ['draft','cancelled'])
            ->whereBetween('invoice_date', [$qStart, $qEnd])
            ->get()
            ->sum(fn ($i) => ($i->is_credit ? -1 : 1) * (float) $i->vat_total);

        $open = Invoice::regular()->open()->get();
        $outstanding = [
            'count' => $open->count(),
            'total' => round($open->sum(fn ($i) => (float) $i->total - (float) $i->paid_total), 2),
        ];

        $overdueList = Invoice::regular()->where('status', 'overdue')->get();
        $overdue = $overdueList->count() ? [
            'count' => $overdueList->count(),
            'total' => round($overdueList->sum(fn ($i) => (float) $i->total - (float) $i->paid_total), 2),
        ] : null;

        $incassoList = Invoice::regular()->where('status', 'incasso')->get();
        $incasso = $incassoList->count() ? [
            'count' => $incassoList->count(),
            'total' => round($incassoList->sum(fn ($i) => (float) $i->total - (float) $i->paid_total), 2),
        ] : null;

        // Top customers this year
        $yr = $now->year;
        $byC = [];
        foreach (Invoice::whereYear('invoice_date', $yr)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->get() as $inv) {
            $cid = $inv->customer_id;
            $byC[$cid] ??= ['name' => $inv->customer_name, 'total' => 0];
            $byC[$cid]['total'] += ($inv->is_credit ? -1 : 1) * (float) $inv->subtotal;
        }
        usort($byC, fn ($a, $b) => $b['total'] <=> $a['total']);
        $top = array_slice(array_map(fn ($c) => [
            'name' => $c['name'],
            'total' => round($c['total'], 2),
        ], $byC), 0, 5);

        return [
            'outstanding' => $outstanding,
            'overdue' => $overdue,
            'incasso' => $incasso,
            'vat' => [
                'quarter' => $qNum,
                'amount' => round($vatThisQuarter, 2),
                'deadline' => $deadline->isoFormat('D MMMM YYYY'),
            ],
            'top_customers' => $top,
        ];
    }
}
