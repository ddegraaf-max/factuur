<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\MerkBundelRegel;
use App\Models\MerkOphaling;
use App\Services\MerkenService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Eigenaar → Merken: per merk van het VvE-pakket of de koppeling is ingesteld,
 * de laatste ophaling, wat er als concept klaarstaat en wat er misging.
 */
class OwnerMerkenController extends Controller
{
    public function index(MerkenService $service)
    {
        $merken = collect($service->merken())->map(function (array $m) use ($service) {
            $team = $service->teamVoor($m);
            $laatste = MerkOphaling::where('merk', $m['code'])->latest('id')->first();
            $regels = MerkBundelRegel::where('merk', $m['code']);

            $concepten = (clone $regels)->whereNotNull('invoice_id')
                ->whereHas('invoice', fn ($q) => $q->where('status', 'draft'))
                ->get()
                ->groupBy('invoice_id')
                ->map(function ($groep, $invoiceId) {
                    $invoice = $groep->first()->invoice;

                    return [
                        'id' => (int) $invoiceId,
                        'klant' => $invoice?->customer_name,
                        'kenmerk' => $groep->first()->klant_kenmerk,
                        'periode' => $groep->first()->periode,
                        'regels' => $groep->count(),
                        'totaal' => $invoice ? (float) $invoice->total : null,
                        'url' => route('invoices.show', $invoiceId),
                    ];
                })->values();

            return [
                'code' => $m['code'],
                'naam' => $m['naam'],
                'url' => $m['url'],
                'sleutel' => $m['sleutel'] !== '',
                'team' => $team ? (Company::withoutGlobalScope('company')->whereKey($team)->value('name') ?? "#{$team} (bestaat niet)") : null,
                'ontbreekt' => $service->ontbreekt($m),
                'laatste' => $laatste ? $this->ophaling($laatste) : null,
                'concepten' => $concepten,
                'niet_afgemeld' => (clone $regels)->whereNull('afgemeld_op')->count(),
                'afmeld_fout' => (clone $regels)->whereNull('afgemeld_op')->whereNotNull('afmeld_fout')->latest('id')->value('afmeld_fout'),
            ];
        })->values();

        $geschiedenis = MerkOphaling::latest('id')->limit(30)->get()->map(fn ($o) => $this->ophaling($o));

        return Inertia::render('Stats/Merken', [
            'merken' => $merken,
            'geschiedenis' => $geschiedenis,
            'team_ingesteld' => filled(config('merken.team_id')),
        ]);
    }

    /** Handmatig ophalen: één merk of alles. */
    public function ophalen(Request $request, MerkenService $service)
    {
        $code = $request->input('merk');
        $door = (string) $request->user()->email;

        if ($code) {
            abort_unless($service->merk((string) $code), 404);
            $uit = [$service->haalOp((string) $code, $door)];
        } else {
            $uit = $service->haalAllesOp($door);
        }

        $fout = collect($uit)->firstWhere('status', 'fout');
        $samenvatting = collect($uit)->map(fn ($o) => "{$o->merk}: {$o->melding}")->implode(' · ');

        return back()->with($fout ? 'error' : 'flash', $samenvatting);
    }

    protected function ophaling(MerkOphaling $o): array
    {
        return [
            'id' => $o->id,
            'merk' => $o->merk,
            'status' => $o->status,
            'melding' => $o->melding,
            'klanten' => $o->klanten,
            'regels' => $o->regels,
            'facturen' => $o->facturen,
            'afmeldfouten' => $o->afmeldfouten,
            'door' => $o->door,
            'tijd' => $o->created_at?->timezone('Europe/Amsterdam')->translatedFormat('j M Y, H:i'),
        ];
    }
}
