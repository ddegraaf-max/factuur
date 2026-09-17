<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\TenderRequest;
use App\Models\TenderRound;
use App\Services\TenderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Uitvraagrondes: openen (vanuit een offerte of los), vergelijken, herinneren,
 * gunnen of sluiten. De pool en de werkpakketten zitten in TenderPoolController,
 * het reactieformulier voor het bedrijf in TenderResponseController.
 */
class TenderController extends Controller
{
    public function __construct(private TenderService $service) {}

    public function index(Request $request): Response
    {
        $status = in_array($request->input('status'), ['open', 'awarded', 'closed', 'all'], true)
            ? $request->input('status')
            : 'open';

        $rounds = TenderRound::with(['workPackage', 'quote', 'requests.subcontractor'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Tenders/Index', [
            'status' => $status,
            'counts' => [
                'open' => TenderRound::where('status', 'open')->count(),
                'awarded' => TenderRound::where('status', 'awarded')->count(),
                'closed' => TenderRound::where('status', 'closed')->count(),
                'all' => TenderRound::count(),
            ],
            'rounds' => $rounds->map(fn (TenderRound $round) => $this->summary($round))->values(),
            'packages' => $this->service->packagesForPicker(auth()->user()->company),
        ]);
    }

    public function show(TenderRound $round): Response
    {
        $round->load(['workPackage', 'quote', 'requests.subcontractor']);
        $budget = $round->budget !== null ? (float) $round->budget : null;
        $priced = $round->requests->filter(fn (TenderRequest $r) => $r->hasPrice());

        return Inertia::render('Tenders/Show', [
            'round' => $this->summary($round) + [
                'description' => $round->description,
                'created_at_label' => $round->created_at?->translatedFormat('j M Y'),
                'awarded_at_label' => $round->awarded_at?->translatedFormat('j M Y'),
                'average' => $priced->count() ? round((float) $priced->avg('price'), 2) : null,
            ],
            'requests' => $round->requests->map(fn (TenderRequest $r) => [
                'id' => $r->id,
                'status' => $r->status,
                'name' => $r->subcontractor?->name,
                'contact_name' => $r->subcontractor?->contact_name,
                'city' => $r->subcontractor?->city,
                'email' => $r->subcontractor?->email,
                'phone' => $r->subcontractor?->phone,
                'price' => $r->hasPrice() ? (float) $r->price : null,
                'delta' => $r->hasPrice() && $budget !== null ? round((float) $r->price - $budget, 2) : null,
                'available_week' => $r->available_week,
                'valid_until_label' => $r->valid_until?->translatedFormat('j M Y'),
                'remarks' => $r->remarks,
                'decline_reason' => $r->decline_reason,
                'attachment_name' => $r->attachment_name,
                'attachment_url' => $r->attachment_path ? route('tenders.attachment', [$round, $r]) : null,
                'sent_at_label' => $r->sent_at?->translatedFormat('j M, H:i'),
                'opened_at_label' => $r->opened_at?->translatedFormat('j M, H:i'),
                'reminded_at_label' => $r->reminded_at?->translatedFormat('j M, H:i'),
                'responded_at_label' => $r->responded_at?->translatedFormat('j M, H:i'),
                'response_url' => $r->responseUrl(),
            ])->values(),
        ]);
    }

    /** Vanuit een (geaccepteerde) offerte: de ronde hoort bij dat project. */
    public function storeFromQuote(Request $request, Quote $quote): RedirectResponse
    {
        return $this->open($request, $quote);
    }

    /** Los, zonder offerte in het pakket. */
    public function store(Request $request): RedirectResponse
    {
        return $this->open($request, null);
    }

    private function open(Request $request, ?Quote $quote): RedirectResponse
    {
        $data = $request->validate([
            'work_package_id' => ['required', 'integer'],
            'subcontractor_ids' => ['required', 'array', 'min:1'],
            'subcontractor_ids.*' => ['integer'],
            'title' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:160'],
            'start_week' => ['nullable', 'string', 'max:12'],
            'deadline' => ['required', 'date', 'after_or_equal:today'],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ], [
            'subcontractor_ids.required' => __('Kies minstens één bedrijf.'),
            'subcontractor_ids.min' => __('Kies minstens één bedrijf.'),
            'deadline.required' => __('Kies een datum waarvoor de bedrijven moeten reageren.'),
            'deadline.after_or_equal' => __('Kies een reactiedatum vanaf vandaag.'),
        ]);

        try {
            $round = $this->service->open(auth()->user()->company, $quote, $data);
        } catch (\DomainException $e) {
            return back()->withErrors(['tender' => $e->getMessage()]);
        }

        return redirect()->route('tenders.show', $round)
            ->with('flash', __('Uitvraag ":title" geopend: :count bedrijven aangeschreven.', [
                'title' => $round->title, 'count' => $round->requests->count(),
            ]));
    }

    public function award(TenderRound $round, TenderRequest $tenderRequest): RedirectResponse
    {
        try {
            $this->service->award($round, $tenderRequest);
        } catch (\DomainException $e) {
            return back()->withErrors(['tender' => $e->getMessage()]);
        }

        return back()->with('flash', __('Gegund aan :name. De opdracht en de afwijzingen zijn gemaild.', [
            'name' => $tenderRequest->subcontractor?->name,
        ]));
    }

    public function remind(TenderRound $round, TenderRequest $tenderRequest): RedirectResponse
    {
        abort_unless((int) $tenderRequest->tender_round_id === (int) $round->id, 404);

        $sent = $this->service->remind($tenderRequest);

        return back()->with('flash', $sent
            ? __('Herinnering gemaild naar :name.', ['name' => $tenderRequest->subcontractor?->name])
            : __('Geen herinnering verstuurd: dit bedrijf heeft al gereageerd of de uitvraag is gesloten.'));
    }

    public function close(TenderRound $round): RedirectResponse
    {
        $this->service->close($round);

        return back()->with('flash', __('Uitvraag gesloten zonder gunning.'));
    }

    /** De offerte-PDF die het bedrijf meestuurde. */
    public function attachment(TenderRound $round, TenderRequest $tenderRequest): StreamedResponse
    {
        abort_unless((int) $tenderRequest->tender_round_id === (int) $round->id && $tenderRequest->attachment_path, 404);
        abort_unless(Storage::disk('local')->exists($tenderRequest->attachment_path), 404);

        return Storage::disk('local')->download($tenderRequest->attachment_path, $tenderRequest->attachment_name ?: 'offerte.pdf');
    }

    private function summary(TenderRound $round): array
    {
        $priced = $round->requests->filter(fn (TenderRequest $r) => $r->hasPrice());
        $awarded = $round->requests->firstWhere('status', 'awarded');

        return [
            'id' => $round->id,
            'title' => $round->title,
            'package' => $round->workPackage?->name,
            'status' => $round->status,
            'quote_id' => $round->quote_id,
            'quote_number' => $round->quote?->number,
            'customer_name' => $round->quote?->customer_name,
            'location' => $round->location,
            'start_week' => $round->start_week,
            'deadline' => $round->deadline->toDateString(),
            'deadline_label' => $round->deadline->translatedFormat('j M Y'),
            'deadline_passed' => $round->deadline->copy()->endOfDay()->isPast(),
            'requested' => $round->requests->count(),
            'responded' => $priced->count(),
            'declined' => $round->requests->where('status', 'declined')->count(),
            'lowest' => $priced->count() ? (float) $priced->min('price') : null,
            'budget' => $round->budget !== null ? (float) $round->budget : null,
            'awarded_to' => $awarded?->subcontractor?->name,
            'awarded_price' => $awarded && $awarded->price !== null ? (float) $awarded->price : null,
        ];
    }
}
