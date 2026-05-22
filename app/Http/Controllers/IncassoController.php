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
                'customer_name' => $inv->customer_name,
                'total' => (float) $inv->total,
                'paid_total' => (float) $inv->paid_total,
                'remaining' => (float) ($inv->total - $inv->paid_total),
                'days_at_armaere' => $inv->incasso_sent_at?->diffInDays(now()) ?? 0,
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
                'name' => 'Armaere Gerechtsdeurwaarders',
                'phone' => '+31 88 100 1000',
                'email' => 'dossiers@armaere.nl',
                'portal' => 'portal.armaere.nl',
                'tagline' => 'Vaste deurwaarder · No cure, no pay tot € 5.000',
            ],
        ]);
    }

    public function send(Invoice $invoice)
    {
        $this->service->send($invoice);
        return back()->with('flash', "Dossier {$invoice->fresh()->incasso_reference} aangemaakt bij Armaere.");
    }

    public function updatePhase(Request $request, Invoice $invoice)
    {
        $phase = $request->validate(['phase' => 'required|in:minnelijk,gerechtelijk,executie'])['phase'];
        $this->service->updatePhase($invoice, $phase);
        return back()->with('flash', 'Incasso-fase bijgewerkt.');
    }
}
