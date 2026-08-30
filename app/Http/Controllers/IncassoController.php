<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\IncassoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IncassoController extends Controller
{
    public function __construct(private IncassoService $service) {}

    public function index()
    {
        // Markt zonder incassopartner (Polen): facturen verkopen aan de factuurkoper.
        if (! \App\Support\Market::hasIncasso()) {
            return $this->wykup();
        }

        $cases = Invoice::where('status', 'incasso')
            ->with('customer')
            ->orderByDesc('incasso_sent_at')
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'number' => $inv->number,
                'incasso_reference' => $inv->incasso_reference,
                'incasso_sent_at' => $inv->incasso_sent_at?->toIso8601String(),
                'incasso_phase' => $inv->incasso_phase,
                // Windykacja (Poolse markt): al te koop aangeboden aan de partner?
                'sale_requested_at' => $inv->sale_requested_at,
                'customer_name' => $inv->customer_name,
                'total' => (float) $inv->total,
                'paid_total' => (float) $inv->paid_total,
                'remaining' => (float) ($inv->total - $inv->paid_total),
                // Hele dagen: Carbon 3 geeft diffInDays als float terug.
                'days_at_armaere' => (int) floor($inv->incasso_sent_at?->diffInDays(now()) ?? 0),
            ]);

        $total = $cases->sum('remaining');
        $oldest = $cases->max('days_at_armaere') ?? 0;

        return Inertia::render('Incasso/Index', [
            'cases' => $cases,
            'stats' => [
                'count' => $cases->count(),
                'total_open' => round($total, 2),
                'oldest_days' => $oldest,
            ],
            'handler' => [
                'name' => \App\Support\Market::incasso('partner_name'),
                'email' => \App\Support\Market::incasso('claims_email'),
                'tagline' => __('Gerechtsdeurwaarder · vaste incassopartner'),
            ],
        ]);
    }

    /** Overzicht "Facturen verkopen": aangeboden facturen en verkoopbare (vervallen, onbetaalde) facturen. */
    private function wykup()
    {
        $row = fn (Invoice $inv) => [
            'id' => $inv->id,
            'number' => $inv->number,
            'customer_name' => $inv->customer_name,
            'due_date' => $inv->due_date?->toDateString(),
            'days_overdue' => $inv->due_date && $inv->due_date->isPast() ? (int) floor($inv->due_date->diffInDays(now())) : 0,
            'remaining' => round((float) $inv->total - (float) $inv->paid_total, 2),
            'sale_requested_at' => $inv->sale_requested_at ? \Illuminate\Support\Carbon::parse($inv->sale_requested_at)->toIso8601String() : null,
        ];

        $base = Invoice::query()->where('is_credit', false)->whereNotIn('status', ['draft', 'paid', 'cancelled']);
        $offered = (clone $base)->whereNotNull('sale_requested_at')->orderByDesc('sale_requested_at')->get()->map($row)->values();
        $candidates = (clone $base)->whereNull('sale_requested_at')->whereNotNull('due_date')->whereDate('due_date', '<', now())->orderBy('due_date')->get()->map($row)->values();

        return Inertia::render('Wykup/Index', [
            'partner' => [
                'name' => \App\Support\Market::wykup('partner_name'),
                'email' => \App\Support\Market::wykup('email'),
                'website' => \App\Support\Market::wykup('website'),
            ],
            'offered' => $offered,
            'candidates' => $candidates,
            'stats' => [
                'offered_count' => $offered->count(),
                'offered_total' => round((float) $offered->sum('remaining'), 2),
                'candidates_count' => $candidates->count(),
                'candidates_total' => round((float) $candidates->sum('remaining'), 2),
            ],
        ]);
    }

    public function send(Invoice $invoice)
    {
        abort_unless(\App\Support\Market::hasIncasso(), 404);

        try {
            $this->service->send($invoice);
        } catch (\DomainException $e) {
            // Bijv. al bij incasso, al betaald, of een creditnota.
            return back()->withErrors(['incasso' => $e->getMessage()]);
        }

        return back()->with('flash', __('Dossier :reference overgedragen aan :partner en per e-mail verzonden.', ['reference' => $invoice->fresh()->incasso_reference, 'partner' => \App\Support\Market::incasso('partner_name')]));
    }

    public function updatePhase(Request $request, Invoice $invoice)
    {
        abort_unless(\App\Support\Market::hasIncasso(), 404);
        $phase = $request->validate(['phase' => 'required|in:minnelijk,gerechtelijk,executie'])['phase'];
        $this->service->updatePhase($invoice, $phase);
        return back()->with('flash', __('Incasso-fase bijgewerkt.'));
    }
}
