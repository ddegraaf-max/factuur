<?php

namespace App\Http\Controllers;

use App\Models\DirectDebitBatch;
use App\Services\SepaDirectDebitService;
use App\Support\Audit;
use App\Support\Iban;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Verkoop → Automatische incasso: batches maken en pain.008-bestanden downloaden voor de bank. */
class DirectDebitController extends Controller
{
    public function __construct(protected SepaDirectDebitService $sepa) {}

    public function index(Request $request)
    {
        $company = $request->user()->company;

        return Inertia::render('DirectDebit/Index', [
            'blockers' => $this->sepa->blockers($company),
            'creditor' => ['iban' => Iban::format($company->iban), 'creditor_id' => $company->sepa_creditor_id],
            'earliest_date' => SepaDirectDebitService::earliestCollectionDate()->toDateString(),
            'collectable' => $this->sepa->collectable($company)->map(fn ($i) => [
                'id' => $i->id,
                'number' => $i->number,
                'customer' => $i->customer_name,
                'iban' => Iban::format($i->customer?->mandate_iban),
                'scheme' => $i->customer?->mandate_type ?: 'CORE',
                'sequence' => $i->customer?->mandate_first_collected_at ? 'RCUR' : 'FRST',
                'due_label' => $i->due_date?->translatedFormat('j M Y'),
                'remaining' => round((float) $i->remaining_amount, 2),
            ]),
            'batches' => DirectDebitBatch::orderByDesc('id')->limit(30)->get()->map(fn ($b) => [
                'id' => $b->id,
                'reference' => $b->reference,
                'collection_label' => $b->collection_date->translatedFormat('j M Y'),
                'created_label' => $b->created_at->translatedFormat('j M Y, H:i'),
                'count' => $b->count,
                'total' => (float) $b->total,
                'downloaded' => $b->downloaded_at !== null,
                'lines' => $b->lines,
            ]),
            'mandates' => \App\Models\Customer::where('mandate_status', 'active')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['integer'],
            'collection_date' => ['required', 'date', 'after_or_equal:' . SepaDirectDebitService::earliestCollectionDate()->toDateString()],
        ], ['collection_date.after_or_equal' => 'De incassodatum moet minimaal drie werkdagen vooruit liggen.']);

        try {
            $batch = $this->sepa->createBatch($request->user()->company, $data['invoice_ids'], Carbon::parse($data['collection_date']), $request->user()->id);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        Audit::log('created', null, "Incassobatch {$batch->reference} aangemaakt: {$batch->count} facturen, € " . number_format((float) $batch->total, 2, ',', '.'));

        return back()->with('flash', "Batch aangemaakt met {$batch->count} facturen. Download het bestand en dien het in bij je bank.");
    }

    public function download(DirectDebitBatch $batch)
    {
        $xml = $this->sepa->pain008($batch);
        $batch->forceFill(['downloaded_at' => now()])->save();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"incasso-{$batch->reference}.xml\"",
        ]);
    }

    public function destroy(DirectDebitBatch $batch)
    {
        $this->sepa->cancel($batch);
        Audit::log('deleted', null, "Incassobatch {$batch->reference} geannuleerd; facturen weer incasseerbaar");

        return back()->with('flash', 'Batch geannuleerd. De facturen staan weer in de lijst.');
    }
}
