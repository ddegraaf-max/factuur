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
                'title' => $overdue->count() . ' achterstallige factu' . ($overdue->count() === 1 ? 'ur' : 'ren'),
                'detail' => 'Totaal openstaand: <span class="amt">€ ' . number_format($total, 2, ',', '.') . '</span>',
            ];
        }

        $incasso = Invoice::regular()->where('status', 'incasso')->count();
        if ($incasso > 0) {
            $insights[] = [
                'severity' => 'warning',
                'title' => $incasso . ' dossier' . ($incasso === 1 ? '' : 's') . ' bij Armaere',
                'detail' => 'De deurwaarder behandelt deze namens jou.',
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
                'title' => "BTW Q{$qNum}-aangifte over {$days} dagen",
                'detail' => 'Deadline ' . $deadline->isoFormat('D MMMM') . '.',
            ];
        }

        $incompleteB2B = Customer::where('type', 'business')->whereNull('kvk_number')->count();
        if ($incompleteB2B > 0) {
            $insights[] = [
                'severity' => 'warning',
                'title' => $incompleteB2B . ' zakelijke klant' . ($incompleteB2B === 1 ? '' : 'en') . ' zonder KVK',
                'detail' => 'Voeg het KVK-nummer toe voor je administratie.',
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'severity' => 'success',
                'title' => 'Alles ziet er goed uit',
                'detail' => 'Geen achterstallige facturen, geen openstaande dossiers.',
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
