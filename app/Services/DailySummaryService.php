<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;

/**
 * Stelt het dagelijkse overzicht samen: wat vraagt vandaag om aandacht?
 */
class DailySummaryService
{
    /**
     * @return array{
     *   has_news: bool,
     *   overdue: array{count:int, amount:float, items:array},
     *   open: array{count:int, amount:float},
     *   due_soon: array{count:int, amount:float, items:array},
     *   paid_yesterday: array{count:int, amount:float, items:array},
     *   drafts: int,
     *   incasso: array{count:int, amount:float}
     * }
     */
    public function gather(Company $company): array
    {
        $invoices = Invoice::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('is_credit', false)
            ->whereIn('status', ['sent', 'partial', 'overdue', 'incasso'])
            ->orderBy('due_date')
            ->get();

        $remaining = fn (Invoice $i) => (float) $i->total - (float) $i->paid_total;
        $today = today();
        $horizon = $today->copy()->addDays(7);

        $overdue = $invoices->filter(
            fn ($i) => $i->status !== 'incasso' && $i->due_date && $i->due_date->lt($today) && $remaining($i) > 0
        );

        $dueSoon = $invoices->filter(
            fn ($i) => $i->status !== 'incasso' && $i->due_date && $i->due_date->gte($today) && $i->due_date->lte($horizon) && $remaining($i) > 0
        );

        $incasso = $invoices->where('status', 'incasso');

        // Betalingen van gisteren (de mail gaat 's ochtends de deur uit).
        $payments = Payment::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->whereDate('paid_on', $today->copy()->subDay())
            ->with(['invoice' => fn ($q) => $q->withoutGlobalScope('company')])
            ->get();

        $drafts = Invoice::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('status', 'draft')
            ->count();

        $summarize = fn ($collection) => [
            'count' => $collection->count(),
            'amount' => round($collection->sum($remaining), 2),
            'items' => $collection->take(5)->map(fn ($i) => [
                'number' => $i->number,
                'customer' => $i->customer_name,
                'amount' => round($remaining($i), 2),
                'due_date' => $i->due_date?->format('d-m-Y'),
                'days_overdue' => $i->due_date && $i->due_date->lt(today()) ? (int) $i->due_date->diffInDays(today()) : 0,
            ])->values()->all(),
        ];

        $openAll = $invoices->filter(fn ($i) => $remaining($i) > 0);

        return [
            // Niets te melden? Dan sturen we ook geen mail.
            'has_news' => $overdue->isNotEmpty() || $dueSoon->isNotEmpty() || $payments->isNotEmpty() || $drafts > 0,
            'overdue' => $summarize($overdue),
            'due_soon' => $summarize($dueSoon),
            'open' => [
                'count' => $openAll->count(),
                'amount' => round($openAll->sum($remaining), 2),
            ],
            'incasso' => [
                'count' => $incasso->count(),
                'amount' => round($incasso->sum($remaining), 2),
            ],
            'paid_yesterday' => [
                'count' => $payments->count(),
                'amount' => round((float) $payments->sum('amount'), 2),
                'items' => $payments->take(5)->map(fn ($p) => [
                    'number' => $p->invoice?->number,
                    'customer' => $p->invoice?->customer_name,
                    'amount' => (float) $p->amount,
                ])->values()->all(),
            ],
            'drafts' => $drafts,
        ];
    }
}
