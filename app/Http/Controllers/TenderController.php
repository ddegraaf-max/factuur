<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\TenderRequest;
use App\Models\TenderRound;
use App\Services\TenderService;
use App\Support\IsoWeek;
use App\Support\StorageUsage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
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
                // Zonder de inhoud: alleen wat de lijst nodig heeft.
                'attachments' => $round->attachments()->get(['id', 'filename', 'mime_type', 'size_bytes'])->map(fn ($a) => [
                    'id' => $a->id,
                    'filename' => $a->filename,
                    'kind' => $a->kind,
                    'size_formatted' => $a->size_formatted,
                ])->values(),
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
                'available_week' => IsoWeek::label($r->available_week),
                'valid_until_label' => $r->valid_until?->translatedFormat('j M Y'),
                'remarks' => $r->remarks,
                'decline_reason' => $r->decline_reason,
                'attachment_name' => $r->attachment_name,
                'attachment_url' => $r->attachment_name ? route('tenders.attachment', [$round, $r]) : null,
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
        ] + self::FILE_RULES, [
            'subcontractor_ids.required' => __('Kies minstens één bedrijf.'),
            'subcontractor_ids.min' => __('Kies minstens één bedrijf.'),
            'deadline.required' => __('Kies een datum waarvoor de bedrijven moeten reageren.'),
            'deadline.after_or_equal' => __('Kies een reactiedatum vanaf vandaag.'),
        ] + $this->fileMessages());

        $files = $request->file('files', []);
        if ($error = $this->storageError($request, $files)) {
            return back()->withErrors(['files' => $error]);
        }
        unset($data['files']);

        try {
            $round = $this->service->open(auth()->user()->company, $quote, $data, $files);
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

    /**
     * Bijlage toevoegen aan een lopende uitvraag. Er gaat geen nieuwe mail uit:
     * het bestand staat meteen op de reactiepagina en gaat mee met herinneringen.
     */
    public function storeAttachments(Request $request, TenderRound $round): RedirectResponse
    {
        if (! $round->isOpen()) {
            return back()->withErrors(['files' => __('Deze uitvraag is al gegund of gesloten; bijlagen toevoegen kan niet meer.')]);
        }

        $request->validate(['files' => ['required', 'array', 'max:10']] + self::FILE_RULES, $this->fileMessages());

        $files = $request->file('files', []);
        if ($error = $this->storageError($request, $files)) {
            return back()->withErrors(['files' => $error]);
        }

        $added = $this->service->attach($round, $files);

        return back()->with('flash', __(':count bijlage(n) toegevoegd. Ze staan nu op de reactiepagina van elk bedrijf en gaan mee met herinneringen.', ['count' => $added]));
    }

    /** De offerte-PDF die het bedrijf meestuurde. */
    public function attachment(TenderRound $round, TenderRequest $tenderRequest): HttpResponse|StreamedResponse
    {
        abort_unless((int) $tenderRequest->tender_round_id === (int) $round->id, 404);

        $name = $tenderRequest->attachment_name ?: 'offerte.pdf';
        if ($file = $tenderRequest->attachments()->latest('id')->first()) {
            $contents = $file->contents();
            abort_if($contents === null, 404);

            return response($contents, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $name, 'offerte'),
                'Content-Length' => (string) strlen($contents),
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        // Offertes van vóór 1.59.0 stonden op schijf.
        abort_unless($tenderRequest->attachment_path && Storage::disk('local')->exists($tenderRequest->attachment_path), 404);

        return Storage::disk('local')->download($tenderRequest->attachment_path, $name);
    }

    /** Zelfde bestandstypen en grootte als de bijlagen bij facturen en offertes. */
    private const FILE_RULES = [
        'files' => ['nullable', 'array', 'max:10'],
        'files.*' => ['file', 'max:10240', 'mimetypes:application/pdf,image/png,image/jpeg,image/webp'],
    ];

    private function fileMessages(): array
    {
        return [
            'files.max' => __('Je kunt hoogstens 10 bijlagen per keer toevoegen.'),
            'files.*.mimetypes' => __('Alleen PDF-, PNG-, JPG- of WEBP-bestanden zijn toegestaan.'),
            'files.*.max' => __('Elk bestand mag maximaal 10 MB groot zijn.'),
        ];
    }

    /** Opslagmeter: boven de limiet geen nieuwe bijlagen (zie App\Support\StorageUsage). */
    private function storageError(Request $request, array $files): ?string
    {
        $incoming = array_sum(array_map(fn ($file) => (int) $file->getSize(), $files));
        $company = $request->user()->company;
        if ($incoming === 0 || StorageUsage::hasRoomFor($company, $incoming)) {
            return null;
        }
        $usage = StorageUsage::for($company);

        return __('De opslag van je administratie is vol (:used van :limit). Verwijder oude bijlagen of stap over op Slim (10 GB).', ['used' => $usage['used_label'], 'limit' => $usage['limit_label']]);
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
            'start_week' => IsoWeek::label($round->start_week),
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
