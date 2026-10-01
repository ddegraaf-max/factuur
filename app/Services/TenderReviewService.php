<?php

namespace App\Services;

use App\Mail\TenderReviewMail;
use App\Models\AiUsageEvent;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\TenderRequest;
use App\Models\TenderRound;
use App\Services\Ai\StructuredClaude;
use App\Support\Audit;
use App\Support\TenderText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Offertecheck: elke binnengekomen prijsopgave van een onderaannemer wordt
 * beoordeeld — is de prijs reëel, hoog of laag, dekt de offerte het gevraagde
 * werk, wat zit er wel en niet in, welke vragen horen erbij en wat is het
 * advies. Bronnen: de aanvraag zelf, de eigen calculatie, de andere prijzen in
 * dezelfde ronde, de eigen geschiedenis voor dit werkpakket en de meegestuurde
 * offerte (PDF of foto). De ondernemer beslist; dit is een tweede paar ogen.
 */
class TenderReviewService
{
    public const VERDICTS = ['reasonable', 'high', 'low', 'unclear'];

    /** Zoveel keer proberen we het automatisch; daarna alleen nog met de knop. */
    private const MAX_ATTEMPTS = 3;

    /** Een bijlage groter dan dit gaat niet naar het model. */
    private const MAX_ATTACHMENT = 12 * 1024 * 1024;

    public function __construct(private StructuredClaude $claude) {}

    public function availableFor(?Company $company): bool
    {
        return $this->claude->enabled() && $company !== null && $company->hasAiAccess();
    }

    /* --------------------------------------------------------------- review */

    /**
     * Beoordeel één prijsopgave en bewaar het resultaat bij de aanvraag.
     *
     * @return array<string, mixed>
     *
     * @throws \DomainException
     */
    public function review(TenderRequest $request, string $source = 'button'): array
    {
        $round = $request->round ?? throw new \DomainException(__('Deze prijsopgave hoort bij geen uitvraag.'));
        $company = $round->company;
        if (! $this->availableFor($company)) {
            throw new \DomainException(__('De offertecheck zit in het Slim-abonnement.'));
        }
        if (! $request->hasPrice()) {
            throw new \DomainException(__('Er is nog geen prijs om te beoordelen.'));
        }

        $facts = $this->facts($request);
        $blocks = $this->attachmentBlocks($request);

        try {
            $json = $this->claude->json($this->prompt($facts, $blocks !== []), $this->schema(), 3000, 'medium', $blocks, 'Offertecheck');
        } catch (\DomainException $e) {
            $request->forceFill(['review_error' => mb_substr($e->getMessage(), 0, 200), 'review_attempts' => (int) $request->review_attempts + 1])->save();
            throw $e;
        }

        $review = $this->sanitize($json) + ['facts' => $facts['numbers'], 'model' => (string) config('services.anthropic.model')];
        $request->forceFill([
            'review' => $review,
            'reviewed_at' => now(),
            'review_error' => null,
            'review_attempts' => (int) $request->review_attempts + 1,
        ])->save();

        AiUsageEvent::record((int) $company->id, 'tender_review', $source);
        Audit::log('updated', $round, __('Offertecheck :name: :verdict', ['name' => $request->subcontractor?->name, 'verdict' => self::verdictLabel($review['verdict'])]), [], $round->company_id);

        return $review;
    }

    /**
     * De automatische ronde: alle prijsopgaven zonder beoordeling, met een
     * mail naar de ondernemer per beoordeling. Geeft het aantal beoordelingen.
     */
    public function reviewPending(): int
    {
        if (! $this->claude->enabled()) {
            return 0;
        }
        $pending = TenderRequest::query()
            ->whereIn('status', ['responded', 'awarded'])
            ->whereNotNull('price')
            ->whereNull('reviewed_at')
            ->where('review_attempts', '<', self::MAX_ATTEMPTS)
            ->where('responded_at', '>=', now()->subDays(30))
            ->with(['round.company', 'round.workPackage', 'subcontractor'])
            ->orderBy('responded_at')
            ->limit(20)
            ->get();

        $done = 0;
        foreach ($pending as $request) {
            $company = $request->round?->company;
            // Geen kosten maken voor een demo of een administratie zonder AI-toegang.
            if (! $company || $company->is_demo || ! $this->availableFor($company)) {
                continue;
            }
            try {
                $review = $this->review($request, 'auto');
                $this->notify($request->fresh(['round.company', 'subcontractor']), $review);
                $done++;
            } catch (\DomainException $e) {
                Log::warning('Offertecheck mislukt', ['request' => $request->id, 'error' => $e->getMessage()]);
            }
        }

        return $done;
    }

    /** De ondernemer krijgt de beoordeling per mail. */
    private function notify(TenderRequest $request, array $review): void
    {
        $company = $request->round?->company;
        $to = $company?->daily_notification_email ?: $company?->email ?: $company?->users()->value('email');
        if (! $to) {
            return;
        }
        try {
            Mail::to($to)->send(new TenderReviewMail($request, $review));
        } catch (\Throwable $e) {
            Log::error('Offertecheck-mail mislukt', ['request' => $request->id, 'error' => $e->getMessage()]);
        }
    }

    /* ---------------------------------------------------------------- feiten */

    /**
     * Alles wat het model naast de offerte moet weten, plus de getallen die we
     * zelf uitrekenen en bij de beoordeling bewaren.
     *
     * @return array{text: string, numbers: array<string, mixed>}
     */
    public function facts(TenderRequest $request): array
    {
        $round = $request->round;
        $price = (float) $request->price;
        $budget = $round->budget !== null ? (float) $round->budget : null;

        $others = $round->requests()->where('id', '!=', $request->id)->whereNotNull('price')
            ->whereIn('status', ['responded', 'awarded', 'rejected'])->with('subcontractor')->get();
        $otherPrices = $others->map(fn (TenderRequest $r) => (float) $r->price)->sort()->values();
        $lowest = $otherPrices->count() ? min($price, (float) $otherPrices->first()) : $price;
        $history = $this->history($round, $request);

        $numbers = [
            'price' => $price,
            'budget' => $budget,
            'vs_budget_pct' => $budget ? round(($price - $budget) / $budget * 100, 1) : null,
            'others' => $otherPrices->all(),
            'rank' => $otherPrices->filter(fn ($p) => $p < $price)->count() + 1,
            'of' => $otherPrices->count() + 1,
            'vs_lowest_pct' => $lowest > 0 ? round(($price - $lowest) / $lowest * 100, 1) : null,
            'history' => $history,
            'vs_history_pct' => $history && $history['median'] > 0 ? round(($price - $history['median']) / $history['median'] * 100, 1) : null,
        ];

        $lines = [];
        $lines[] = 'UITVRAAG';
        $lines[] = 'Onderdeel: ' . $round->title;
        if ($round->workPackage) {
            $lines[] = 'Werkpakket: ' . $round->workPackage->name . (filled($round->workPackage->description) ? ' — ' . $round->workPackage->description : '');
        }
        if ($round->location) {
            $lines[] = 'Locatie: ' . $round->location;
        }
        if ($round->start_week) {
            $lines[] = 'Gewenste start: week ' . $round->start_week;
        }
        $body = TenderText::body($round->description);
        $lines[] = 'Omschrijving van het gevraagde werk:';
        $lines[] = $body !== '' ? $body : '(geen omschrijving meegegeven)';
        $lines[] = '';
        $lines[] = 'EIGEN CALCULATIE VAN DE AANNEMER';
        $lines[] = $budget !== null ? 'Begroot voor dit onderdeel (excl. btw): ' . number_format($budget, 2, ',', '.') : 'Geen eigen calculatie ingevuld.';
        $lines[] = '';
        $lines[] = 'DEZE PRIJSOPGAVE';
        $lines[] = 'Bedrijf: ' . ($request->subcontractor?->name ?? '?') . ($request->subcontractor?->city ? ' (' . $request->subcontractor->city . ')' : '');
        $lines[] = 'Prijs (excl. btw): ' . number_format($price, 2, ',', '.');
        if ($request->available_week) {
            $lines[] = 'Beschikbaar vanaf: week ' . $request->available_week;
        }
        if ($request->valid_until) {
            $lines[] = 'Prijs geldig tot: ' . $request->valid_until->toDateString();
        }
        $lines[] = 'Opmerkingen van het bedrijf: ' . (filled($request->remarks) ? trim((string) $request->remarks) : '(geen)');
        $lines[] = $request->attachment_name ? 'Eigen offerte meegestuurd: ' . $request->attachment_name . ' (zie bijgevoegd document)' : 'Geen eigen offerte meegestuurd; alleen prijs en opmerkingen.';
        $lines[] = '';
        $lines[] = 'ANDERE PRIJZEN IN DEZELFDE UITVRAAG';
        if ($others->count()) {
            foreach ($others as $o) {
                $lines[] = '- ' . ($o->subcontractor?->name ?? '?') . ': ' . number_format((float) $o->price, 2, ',', '.') . (filled($o->remarks) ? ' — ' . mb_substr(trim((string) $o->remarks), 0, 300) : '');
            }
        } else {
            $lines[] = '(nog geen andere prijzen)';
        }
        $lines[] = '';
        $lines[] = 'EERDERE PRIJZEN VAN DEZE AANNEMER VOOR HETZELFDE WERKPAKKET (afgelopen 2 jaar)';
        $lines[] = $history
            ? "{$history['n']} prijsopgaven: laagste " . number_format($history['min'], 2, ',', '.') . ', mediaan ' . number_format($history['median'], 2, ',', '.') . ', hoogste ' . number_format($history['max'], 2, ',', '.')
                . ($history['awarded_median'] !== null ? '; mediaan van de gegunde prijzen ' . number_format($history['awarded_median'], 2, ',', '.') : '')
                . ' (let op: andere projecten, andere omvang — alleen een grove indicatie)'
            : '(geen eerdere prijzen bekend)';

        return ['text' => implode("\n", $lines), 'numbers' => $numbers];
    }

    /**
     * Eerdere prijzen van deze administratie voor hetzelfde werkpakket.
     *
     * @return array{n: int, min: float, median: float, max: float, awarded_median: float|null}|null
     */
    private function history(TenderRound $round, TenderRequest $request): ?array
    {
        if (! $round->work_package_id) {
            return null;
        }
        $rows = TenderRequest::query()
            ->join('tender_rounds', 'tender_rounds.id', '=', 'tender_requests.tender_round_id')
            ->where('tender_rounds.company_id', $round->company_id)
            ->where('tender_rounds.work_package_id', $round->work_package_id)
            ->where('tender_rounds.id', '!=', $round->id)
            ->where('tender_requests.id', '!=', $request->id)
            ->whereNotNull('tender_requests.price')
            ->whereIn('tender_requests.status', ['responded', 'awarded', 'rejected'])
            ->where('tender_requests.responded_at', '>=', now()->subYears(2))
            ->get(['tender_requests.price', 'tender_requests.status']);
        if ($rows->count() < 2) {
            return null;
        }
        $prices = $rows->map(fn ($r) => (float) $r->price)->sort()->values();
        $awarded = $rows->where('status', 'awarded')->map(fn ($r) => (float) $r->price)->sort()->values();

        return [
            'n' => $prices->count(),
            'min' => (float) $prices->first(),
            'median' => $this->median($prices),
            'max' => (float) $prices->last(),
            'awarded_median' => $awarded->count() ? $this->median($awarded) : null,
        ];
    }

    /** @param  Collection<int, float>  $sorted */
    private function median(Collection $sorted): float
    {
        $n = $sorted->count();
        $mid = intdiv($n, 2);

        return $n % 2 ? (float) $sorted[$mid] : round(((float) $sorted[$mid - 1] + (float) $sorted[$mid]) / 2, 2);
    }

    /**
     * De meegestuurde offerte als document- of afbeeldingsblok voor het model.
     *
     * @return array<int, array<string, mixed>>
     */
    private function attachmentBlocks(TenderRequest $request): array
    {
        $file = $request->attachments()->first();
        if (! $file instanceof Attachment) {
            return [];
        }
        $mime = strtolower((string) $file->mime_type);
        if (! in_array($mime, ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'], true) || (int) $file->size_bytes > self::MAX_ATTACHMENT) {
            return [];
        }
        $bytes = $file->contents();
        if (! $bytes) {
            return [];
        }

        return [$mime === 'application/pdf'
            ? ['type' => 'document', 'source' => ['type' => 'base64', 'mediaType' => 'application/pdf', 'data' => base64_encode($bytes)]]
            : ['type' => 'image', 'source' => ['type' => 'base64', 'mediaType' => $mime, 'data' => base64_encode($bytes)]]];
    }

    /* ---------------------------------------------------------------- model */

    private function prompt(array $facts, bool $withAttachment): string
    {
        $language = market('locale') === 'pl' ? 'Pools' : 'Nederlands';

        return <<<PROMPT
        Je bent een ervaren calculator/werkvoorbereider in de Nederlandse bouw en installatiebranche. Een aannemer heeft een onderaannemer om een prijs gevraagd voor een onderdeel van een project. Beoordeel de binnengekomen prijsopgave voor de aannemer: is de prijs reëel, aan de hoge kant of opvallend laag, en dekt de offerte het gevraagde werk?

        Gebruik alles hieronder: de aanvraag, de eigen calculatie van de aannemer, de andere prijzen in dezelfde uitvraag, de eerdere prijzen voor dit werkpakket{$this->attachmentNote($withAttachment)}. Gebruik daarnaast je eigen kennis van gangbare prijzen in Nederland (eenheidsprijzen per m², m¹, stuk of uur, materiaalkosten, uurtarieven) — maar noem die alleen als de omschrijving genoeg houvast geeft (hoeveelheden, afmetingen, soort werk) en wees eerlijk over de onzekerheid.

        Regels:
        - Schrijf in het {$language}, kort en zakelijk, gericht aan de aannemer (je/jij). Geen inleidingen.
        - "verdict": reasonable (reëel), high (aan de hoge kant), low (opvallend laag — vaak een risico: iets vergeten of niet alles inbegrepen), unclear (te weinig houvast). Bij twijfel tussen reëel en hoog/laag: kies reëel en zeg wat je nodig hebt om het scherper te zien.
        - Weeg de bronnen: andere prijzen in dezelfde uitvraag en de eigen calculatie wegen zwaarder dan de geschiedenis; een enkele afwijkende prijs is geen bewijs. Een prijs die veel lager is dan de rest verdient net zo goed een waarschuwing als een hoge.
        - Let op wat er wel en niet in zit: materiaal, arbeid, steiger/hulpmiddelen, afvoer, meerwerk, stelposten, btw verlegd, betalingsvoorwaarden, geldigheid, levertijd. Wat de offerte niet noemt terwijl de aanvraag het vraagt, is een "scope_gap".
        - "market_estimate": een bandbreedte excl. btw voor het gevraagde werk als je die met redelijke zekerheid kunt geven; anders null met in "basis" waarom niet. Verzin geen getallen.
        - "questions": concrete vragen aan het bedrijf om onduidelijkheden weg te nemen, hooguit vijf, elk één zin.
        - "advice": wat de aannemer nu het beste doet: gunnen, onderhandelen (met welk bedrag of percentage), eerst vragen stellen, of afwachten op andere prijzen. Eén tot drie zinnen.
        - Verzin niets dat niet uit de gegevens of uit algemene vakkennis volgt.

        {$facts['text']}
        PROMPT;
    }

    private function attachmentNote(bool $withAttachment): string
    {
        return $withAttachment ? ' en de meegestuurde offerte (bijgevoegd document; lees die volledig, inclusief kleine lettertjes en uitsluitingen)' : '';
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        $list = fn (string $description) => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => $description];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'verdict' => ['type' => 'string', 'enum' => self::VERDICTS],
                'confidence' => ['type' => 'string', 'enum' => ['high', 'medium', 'low'], 'description' => 'Hoe zeker het oordeel is.'],
                'headline' => ['type' => 'string', 'description' => 'Eén regel, hooguit 90 tekens: het oordeel in gewone taal, bijv. "Reëel: 4% onder je calculatie, steiger niet inbegrepen".'],
                'summary' => ['type' => 'string', 'description' => 'Twee tot vier zinnen: waarom dit oordeel.'],
                'price_basis' => ['type' => 'string', 'description' => 'Hoe de prijs is opgebouwd volgens de offerte (eenheidsprijzen, uren, materiaal), of "alleen een totaalbedrag".'],
                'comparison' => ['type' => 'string', 'description' => 'De vergelijking met de calculatie, de andere prijzen en de geschiedenis, in één of twee zinnen met getallen.'],
                'included' => $list('Wat volgens de offerte/opmerkingen is inbegrepen.'),
                'excluded' => $list('Wat expliciet is uitgesloten, als stelpost staat of onder voorbehoud is.'),
                'scope_gaps' => $list('Werk uit de aanvraag dat de offerte niet noemt of niet dekt.'),
                'market_estimate' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'low' => ['type' => ['number', 'null'], 'description' => 'Onderkant van een reële bandbreedte, excl. btw.'],
                        'high' => ['type' => ['number', 'null'], 'description' => 'Bovenkant van een reële bandbreedte, excl. btw.'],
                        'basis' => ['type' => 'string', 'description' => 'Waarop de bandbreedte rust (hoeveelheden × eenheidsprijzen), of waarom die niet te geven is.'],
                    ],
                    'required' => ['low', 'high', 'basis'],
                ],
                'questions' => $list('Vragen aan het bedrijf, hooguit vijf.'),
                'advice' => ['type' => 'string', 'description' => 'Wat de aannemer nu het beste doet.'],
            ],
            'required' => ['verdict', 'confidence', 'headline', 'summary', 'price_basis', 'comparison', 'included', 'excluded', 'scope_gaps', 'market_estimate', 'questions', 'advice'],
        ];
    }

    /** @return array<string, mixed> */
    private function sanitize(array $json): array
    {
        $text = fn ($v, int $max) => is_string($v) ? mb_substr(trim($v), 0, $max) : '';
        $list = fn ($v, int $max = 8) => collect(is_array($v) ? $v : [])->filter(fn ($s) => is_string($s))->map(fn ($s) => mb_substr(trim($s), 0, 300))->filter()->take($max)->values()->all();
        $num = fn ($v) => is_numeric($v) && $v > 0 ? round((float) $v, 2) : null;
        $verdict = in_array($json['verdict'] ?? null, self::VERDICTS, true) ? $json['verdict'] : 'unclear';

        return [
            'verdict' => $verdict,
            'confidence' => in_array($json['confidence'] ?? null, ['high', 'medium', 'low'], true) ? $json['confidence'] : 'low',
            'headline' => $text($json['headline'] ?? '', 140),
            'summary' => $text($json['summary'] ?? '', 1200),
            'price_basis' => $text($json['price_basis'] ?? '', 500),
            'comparison' => $text($json['comparison'] ?? '', 600),
            'included' => $list($json['included'] ?? []),
            'excluded' => $list($json['excluded'] ?? []),
            'scope_gaps' => $list($json['scope_gaps'] ?? []),
            'market_estimate' => [
                'low' => $num($json['market_estimate']['low'] ?? null),
                'high' => $num($json['market_estimate']['high'] ?? null),
                'basis' => $text($json['market_estimate']['basis'] ?? '', 500),
            ],
            'questions' => $list($json['questions'] ?? [], 5),
            'advice' => $text($json['advice'] ?? '', 600),
        ];
    }

    public static function verdictLabel(string $verdict): string
    {
        return match ($verdict) {
            'reasonable' => __('reëel'),
            'high' => __('aan de hoge kant'),
            'low' => __('opvallend laag'),
            default => __('onduidelijk'),
        };
    }
}
