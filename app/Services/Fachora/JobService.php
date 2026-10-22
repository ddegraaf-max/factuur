<?php

namespace App\Services\Fachora;

use App\Models\Customer;
use App\Models\FachoraFile;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Models\ServiceJobEvent;
use App\Models\ServiceJobExtra;
use App\Models\ServiceJobOffer;
use App\Models\ServiceReview;
use App\Services\Ai\StructuredClaude;
use App\Services\InvoiceManager;
use App\Support\Fachora;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * De opdrachtcyclus van Fachora.
 *
 *  pending      klant heeft de aanvraag ingevuld; wacht op de klik in de bevestigingsmail
 *  open         aangeboden aan een handvol Verified bedrijven (ServiceJobOffer per bedrijf)
 *  quoted       minstens één prijs binnen
 *  no_match     geen Verified bedrijf in de buurt: de eigenaar hoort het en belt
 *  agreed       klant koos een bedrijf; met Protect wacht de opdracht op de reservering
 *  scheduled    afspraak staat (en bij Protect: bedrag gereserveerd)
 *  in_progress  bedrijf heeft gemeld dat het begonnen is
 *  done         bedrijf meldt klaar; de klant keurt goed (of stilzwijgend na vijf dagen)
 *  accepted     afgerond: uitbetaling, conceptfactuur in de administratie van het bedrijf, beoordeling
 *  disputed     klant niet akkoord; betaling blijft staan tot de eigenaar beslist
 *  cancelled | expired
 *
 * Bedrijven krijgen alleen mail als ze zelf hun profiel hebben geclaimd (Notifier/ConsentPolicy).
 */
class JobService
{
    public const LIMIT = ['hour' => 5, 'day' => 15, 'resend_hour' => 5];

    public function __construct(
        private ProviderService $providers,
        private GeocodeService $geo,
        private Notifier $notifier,
        private ProtectService $protect,
    ) {}

    /* ---------- aanvraag ---------- */

    public function mayCreate(Request $request): bool
    {
        return ! RateLimiter::tooManyAttempts('fachora-job-hour:' . $request->ip(), self::LIMIT['hour'])
            && ! RateLimiter::tooManyAttempts('fachora-job-day:' . $request->ip(), self::LIMIT['day']);
    }

    /**
     * Stap 1: de aanvraag vastleggen en de klant vragen zijn e-mailadres te
     * bevestigen. Tot die klik bestaat de opdracht voor bedrijven niet.
     *
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $files
     */
    public function create(array $data, array $files, Request $request): ServiceJob
    {
        $coords = $this->geo->lookup($data['postal_code'] ?? null, $data['city'] ?? null);
        $locale = app()->getLocale() === 'en' ? 'en' : 'pl';

        $attrs = [
            'token' => bin2hex(random_bytes(32)),
            'status' => 'pending',
            'category' => $data['category'],
            'title' => mb_substr(trim((string) ($data['title'] ?? '')) ?: Fachora::categoryName($data['category'], 'pl'), 0, 160),
            'description' => trim((string) $data['description']),
            'urgency' => in_array($data['urgency'] ?? null, (array) config('fachora.urgencies'), true) ? $data['urgency'] : 'week',
            'wanted_date' => $data['wanted_date'] ?? null,
            'time_window' => $data['time_window'] ?? null,
            'language' => Fachora::isLanguage($data['language'] ?? null) ? $data['language'] : 'pl',
            'customer_name' => mb_substr(trim((string) $data['customer_name']), 0, 120),
            'customer_email' => mb_strtolower(trim((string) $data['customer_email'])),
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_locale' => $locale,
            'postal_code' => $data['postal_code'] ?? null,
            'city' => mb_substr(trim((string) $data['city']), 0, 120),
            'lat' => $coords['lat'] ?? null,
            'lng' => $coords['lng'] ?? null,
            'creator_ip' => $request->ip(),
        ];

        // Het kenmerk is uniek; twee gelijktijdige aanvragen kunnen hetzelfde volgnummer
        // berekenen, dan krijgt de tweede gewoon het volgende.
        $job = null;
        for ($attempt = 0; $attempt < 3 && $job === null; $attempt++) {
            try {
                $job = ServiceJob::create($attrs + ['reference' => ServiceJob::nextReference()]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                if ($attempt === 2) {
                    throw $e;
                }
            }
        }

        foreach ($files as $file) {
            FachoraFile::fromUpload('job', $job->id, 'photo', $file);
        }

        RateLimiter::hit('fachora-job-hour:' . $request->ip(), 3600);
        RateLimiter::hit('fachora-job-day:' . $request->ip(), 86400);
        $this->log($job, 'created', 'customer', __('Aanvraag ingevuld; wacht op bevestiging van :email', ['email' => $job->customer_email]));
        $this->mailConfirm($job);

        return $job;
    }

    public function mailConfirm(ServiceJob $job): bool
    {
        $sent = $this->notifier->customer($job, 'confirm');
        if ($sent) {
            $job->increment('confirm_mails');
        }

        return $sent;
    }

    /** @return 'sent'|'limit'|'failed' */
    public function resendConfirm(ServiceJob $job, Request $request): string
    {
        if ($job->confirm_mails > 3 || RateLimiter::tooManyAttempts('fachora-resend:' . $request->ip(), self::LIMIT['resend_hour'])) {
            return 'limit';
        }
        RateLimiter::hit('fachora-resend:' . $request->ip(), 3600);

        return $this->mailConfirm($job) ? 'sent' : 'failed';
    }

    /** Stap 2: de klant klikt. Daarna wordt de opdracht verrijkt en aangeboden. */
    public function confirm(ServiceJob $job, Request $request): bool
    {
        $first = ServiceJob::query()->whereKey($job->id)->where('status', 'pending')->update(['status' => 'open', 'confirmed_at' => now()]) === 1;
        if (! $first) {
            return false;
        }
        $job->refresh();
        $this->log($job, 'confirmed', 'customer', __('E-mailadres bevestigd; opdracht actief'));
        $this->enrich($job);

        // 1.86: de klant kiest zelf de bedrijven (als er in de buurt iemand is om uit te kiezen).
        if ((bool) config('fachora.customer_choice', true) && $this->providers->choiceCandidates($job, 1)->isNotEmpty()) {
            $job->forceFill(['status' => 'selecting', 'request_fee' => $this->requestFee()])->save();
            $this->log($job, 'selecting', 'system', __('Klant kiest zelf de bedrijven'));

            return true;
        }

        $this->dispatch($job);
        // Geen Verified bedrijf in de buurt? Dan zegt de mail dat meteen, in plaats van "we bieden hem aan".
        $job->refresh();
        $this->notifier->customer($job, $job->status === 'no_match' ? 'no_match' : 'confirmed');

        return true;
    }

    /* ---------- de klant kiest zelf (1.86) ---------- */

    /** De bijdrage per aanvraag in złoty (0 = gratis, of geen Stripe: dan ook gratis). */
    public function requestFee(): float
    {
        $fee = max(0, (float) config('fachora.request_fee', 10));

        return $fee > 0 && $this->protect->stripeConfigured() ? round($fee, 2) : 0.0;
    }

    /**
     * De klant kiest 1 tot max_offers bedrijven uit de lijst. Zonder bijdrage
     * gaat de aanvraag meteen weg; met bijdrage geeft dit het Checkout-adres.
     *
     * @param  list<int>  $providerIds
     * @return string|null  het betaaladres, of null als de aanvraag al is verstuurd
     */
    public function selectProviders(ServiceJob $job, array $providerIds): ?string
    {
        if (! $job->isSelecting()) {
            throw new \DomainException(__('Voor deze opdracht kun je geen bedrijven (meer) kiezen.'));
        }
        $max = (int) config('fachora.max_offers', 5);
        $allowed = $this->providers->choiceCandidates($job)->pluck('id')->all();
        $ids = array_values(array_unique(array_map('intval', $providerIds)));
        $ids = array_values(array_filter($ids, fn (int $id) => in_array($id, $allowed, true)));
        if ($ids === []) {
            throw new \DomainException(__('Kies minstens één bedrijf.'));
        }
        if (count($ids) > $max) {
            throw new \DomainException(__('Kies maximaal :n bedrijven.', ['n' => $max]));
        }

        $fee = $this->requestFee();
        $job->forceFill(['selected_providers' => $ids, 'request_fee' => $fee])->save();
        $this->log($job, 'selected', 'customer', __('Klant koos :n bedrijven', ['n' => count($ids)]));

        if ($fee <= 0 || $job->request_paid_at !== null) {
            $this->submitSelection($job);

            return null;
        }

        return $this->protect->requestCheckoutUrl($job, $fee, route('fachora.job.request.paid', $job->token), $job->url());
    }

    /** Terug van Stripe (of via de planner): betaald? Dan gaat de aanvraag weg. */
    public function syncRequestPayment(ServiceJob $job): bool
    {
        if (! $job->isSelecting() || empty($job->selected_providers)) {
            return false;
        }
        if ($job->request_paid_at === null) {
            if (! $this->protect->requestPaid($job)) {
                return false;
            }
            $job->forceFill(['request_paid_at' => now()])->save();
            $this->log($job, 'request_paid', 'customer', __('Aanvraag betaald: :amount', ['amount' => Fachora::money($job->request_fee)]));
        }
        $this->submitSelection($job);

        return true;
    }

    /** De gekozen bedrijven krijgen de aanvraag. */
    public function submitSelection(ServiceJob $job): int
    {
        $first = ServiceJob::query()->whereKey($job->id)->where('status', 'selecting')->update(['status' => 'open']) === 1;
        if (! $first) {
            return 0;
        }
        $job->refresh();
        $sent = $this->dispatch($job, array_map('intval', (array) $job->selected_providers), 'customer');
        $job->refresh();
        $this->notifier->customer($job, $job->status === 'no_match' ? 'no_match' : 'confirmed');

        return $sent;
    }

    /** Betalingen die via Stripe binnenkwamen terwijl de klant de pagina al had gesloten (planner). */
    public function syncPendingRequests(): int
    {
        $done = 0;
        foreach (ServiceJob::query()->where('status', 'selecting')->whereNotNull('request_session_id')->whereNull('request_paid_at')
            ->where('updated_at', '>', now()->subDays(2))->limit(50)->get() as $job) {
            $done += $this->syncRequestPayment($job) ? 1 : 0;
        }

        return $done;
    }

    /** AI: nette Poolse omschrijving voor het bedrijf, een prijsklasse en eventuele vragen. Mag mislukken. */
    public function enrich(ServiceJob $job): void
    {
        $ai = app(StructuredClaude::class);
        if (! $ai->enabled()) {
            return;
        }
        try {
            $schema = [
                'type' => 'object',
                'properties' => [
                    'title_pl' => ['type' => 'string'],
                    'description_pl' => ['type' => 'string'],
                    'summary_en' => ['type' => 'string'],
                    'estimate_low' => ['type' => 'number'],
                    'estimate_high' => ['type' => 'number'],
                    'questions_pl' => ['type' => 'array', 'items' => ['type' => 'string']],
                ],
                'required' => ['title_pl', 'description_pl', 'summary_en', 'estimate_low', 'estimate_high', 'questions_pl'],
                'additionalProperties' => false,
            ];
            $prompt = "Jesteś asystentem platformy Fachora (Polska). Klient opisał zlecenie dla fachowca.\n"
                . 'Kategoria: ' . Fachora::categoryName($job->category, 'pl') . "\nMiasto: {$job->city}\nJęzyk klienta: {$job->language}\nPilność: {$job->urgency}\n"
                . "Opis klienta (może być w innym języku):\n\"\"\"\n{$job->description}\n\"\"\"\n\n"
                . 'Zwróć: krótki tytuł po polsku (max 8 słów), rzeczową polską wersję opisu dla fachowca (bez danych kontaktowych), '
                . 'jednozdaniowe streszczenie po angielsku, realistyczny przedział ceny brutto w PLN dla takiej usługi w Polsce w 2026 r. '
                . '(estimate_low/estimate_high, robocizna i dojazd, bez materiałów o dużej wartości) oraz maksymalnie trzy pytania po polsku, '
                . 'które fachowiec powinien zadać przed wyceną. Jeśli opis jest zbyt ogólny, przedział może być szeroki.';
            $result = $ai->json($prompt, $schema, 1200, 'low', [], 'Fachora intake');
            $job->forceFill([
                'description_pl' => mb_substr((string) ($result['description_pl'] ?? ''), 0, 4000) ?: null,
                'estimate_low' => isset($result['estimate_low']) ? max(0, round((float) $result['estimate_low'])) : null,
                'estimate_high' => isset($result['estimate_high']) ? max(0, round((float) $result['estimate_high'])) : null,
                'ai' => [
                    'title_pl' => mb_substr((string) ($result['title_pl'] ?? ''), 0, 160),
                    'summary_en' => mb_substr((string) ($result['summary_en'] ?? ''), 0, 500),
                    'questions_pl' => array_slice(array_values(array_filter((array) ($result['questions_pl'] ?? []), 'is_string')), 0, 3),
                    'at' => now()->toIso8601String(),
                ],
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('Fachora: AI-verrijking mislukt', ['job' => $job->id, 'error' => $e->getMessage()]);
        }
    }

    /* ---------- aanbieden ---------- */

    /**
     * Biedt de opdracht aan bij Verified bedrijven (of bij de meegegeven
     * bedrijven, als de eigenaar zelf kiest). Alleen bedrijven met toestemming
     * krijgen mail; wie al een aanbieding had, krijgt geen tweede.
     *
     * @param  list<int>|null  $providerIds
     */
    public function dispatch(ServiceJob $job, ?array $providerIds = null, string $actor = 'system'): int
    {
        if (! in_array($job->status, ['open', 'quoted', 'no_match'], true)) {
            throw new \DomainException(__('Deze opdracht kan niet (meer) worden aangeboden.'));
        }
        $already = $job->offers()->pluck('provider_id')->all();

        if ($providerIds !== null && $actor === 'customer') {
            // 1.86: door de klant zelf gekozen; ook bedrijven die hun profiel nog niet claimden.
            $candidates = Provider::query()->whereIn('id', $providerIds)->whereNotIn('id', $already)->get()
                ->filter(fn (Provider $p) => ConsentPolicy::mayEmailRequest($p) && in_array($p->status, ['unclaimed', 'claimed', 'pending', 'verified'], true))
                ->take((int) config('fachora.max_offers', 5))->values();
        } elseif ($providerIds !== null) {
            $candidates = Provider::query()->whereIn('id', $providerIds)->whereNotIn('id', $already)->get()
                ->filter(fn (Provider $p) => ConsentPolicy::mayEmail($p) && in_array($p->status, ['claimed', 'pending', 'verified'], true))->values();
        } else {
            $candidates = $this->providers->match($job, (int) config('fachora.max_offers', 5), $already);
            if ($candidates->isEmpty() && $job->language !== 'pl') {
                $candidates = $this->providers->match($job, (int) config('fachora.max_offers', 5), $already, requireLanguage: false);
                if ($candidates->isNotEmpty()) {
                    $job->forceFill(['ai' => array_merge($job->ai ?? [], ['language_fallback' => true])])->save();
                }
            }
        }

        if ($candidates->isEmpty()) {
            if ($job->offers()->count() === 0) {
                $job->forceFill(['status' => 'no_match'])->save();
                $this->log($job, 'no_match', 'system', __('Geen Verified bedrijf in de buurt; de eigenaar is ingelicht'));
                if ($job->owner_notified_at === null) {
                    $job->forceFill(['owner_notified_at' => now()])->save();
                    $this->notifier->owner('no_match', [], $job);
                }
            }

            return 0;
        }

        $wave = (int) $job->waves + 1;
        $sent = 0;
        foreach ($candidates as $provider) {
            $offer = $job->offers()->create([
                'provider_id' => $provider->id,
                'token' => bin2hex(random_bytes(32)),
                'status' => 'sent',
                'wave' => $wave,
            ]);
            $notified = $actor === 'customer'
                ? $this->notifier->providerRequest($provider, $job, $offer)
                : $this->notifier->provider($provider, 'job', [], $job, $offer);
            if ($notified) {
                $offer->forceFill(['sent_at' => now()])->save();
                $sent++;
            }
        }

        $job->forceFill([
            'status' => $job->status === 'quoted' ? 'quoted' : 'open',
            'offered_at' => $job->offered_at ?: now(),
            'offers_deadline_at' => now()->addHours((int) config('fachora.offer_hours', 48)),
            'waves' => $wave,
        ])->save();
        $this->log($job, 'offered', $actor, __('Aangeboden aan :n bedrijven (ronde :wave)', ['n' => $candidates->count(), 'wave' => $wave]));

        return $sent;
    }

    public function openOffer(ServiceJobOffer $offer): void
    {
        if ($offer->opened_at === null) {
            $offer->forceFill(['opened_at' => now()])->save();
        }
    }

    /** Het bedrijf geeft prijs en moment door. @param  array<string, mixed>  $data */
    public function respond(ServiceJobOffer $offer, array $data): void
    {
        $job = $offer->job;
        if (! $job->canChoose() || ! in_array($offer->status, ['sent', 'responded'], true)) {
            throw new \DomainException(__('Deze aanbieding is niet meer open.'));
        }
        $provider = $offer->provider;
        if ($provider->claimed_at === null && $provider->terms_accepted_at === null) {
            if (! filter_var($data['accept_terms'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                throw new \DomainException(__('Ga eerst akkoord met de voorwaarden van Fachora.'));
            }
            // Akkoord op de tokenpagina: vanaf nu mag Fachora dit bedrijf over deze opdracht mailen.
            $provider->forceFill(array_filter([
                'terms_accepted_at' => now(),
                'terms_version' => (string) config('fachora.terms_version'),
                'consent_at' => $provider->consent_at ?? now(),
                'consent_source' => $provider->consent_source ?? 'offer_terms',
            ], fn ($v) => $v !== null))->save();
            $this->log($job, 'terms_accepted', 'provider', __(':provider ging akkoord met de voorwaarden', ['provider' => $provider->display_name]));
        }

        $first = $offer->responded_at === null;
        $offer->forceFill([
            'status' => 'responded',
            'responded_at' => $offer->responded_at ?: now(),
            'price' => round((float) $data['price'], 2),
            'price_type' => in_array($data['price_type'] ?? null, ['fixed', 'hourly', 'estimate'], true) ? $data['price_type'] : 'fixed',
            'callout' => isset($data['callout']) && $data['callout'] !== '' ? round((float) $data['callout'], 2) : null,
            'hourly' => isset($data['hourly']) && $data['hourly'] !== '' ? round((float) $data['hourly'], 2) : null,
            'available_at' => $data['available_at'] ?? null,
            'available_note' => isset($data['available_note']) ? mb_substr((string) $data['available_note'], 0, 120) : null,
            'remarks' => isset($data['remarks']) ? mb_substr((string) $data['remarks'], 0, 2000) : null,
            'decline_reason' => null,
        ])->save();

        $job->forceFill(['status' => 'quoted'])->save();
        $this->providers->recalculateResponseTime($offer->provider);
        $this->log($job, 'responded', 'provider', __(':provider gaf een prijs: :amount', ['provider' => $offer->provider->display_name, 'amount' => Fachora::money($offer->price)]));
        if ($first) {
            $this->notifier->customer($job, 'offer', ['offer_id' => $offer->id]);
        }
    }

    public function decline(ServiceJobOffer $offer, ?string $reason = null): void
    {
        if (! in_array($offer->status, ['sent', 'responded'], true)) {
            throw new \DomainException(__('Deze aanbieding is niet meer open.'));
        }
        $offer->forceFill(['status' => 'declined', 'responded_at' => $offer->responded_at ?: now(), 'decline_reason' => $reason ? mb_substr($reason, 0, 500) : null])->save();
        $this->log($offer->job, 'declined', 'provider', __(':provider zegde af', ['provider' => $offer->provider->display_name]));
        // Iedereen afgezegd? Dan direct een volgende ronde.
        $job = $offer->job->fresh();
        if ($job->canChoose() && $job->offers()->whereIn('status', ['sent', 'responded'])->doesntExist()) {
            $this->dispatch($job);
        }
    }

    /* ---------- keuze en afspraak ---------- */

    /**
     * De klant kiest een bedrijf. Pas nu krijgt dat bedrijf het adres en het
     * telefoonnummer; de anderen horen dat de keuze is gevallen.
     *
     * @param  array<string, mixed>  $data  address_line, customer_phone, note
     */
    public function choose(ServiceJob $job, ServiceJobOffer $offer, array $data): void
    {
        if (! $job->canChoose() || $offer->service_job_id !== $job->id || ! $offer->hasPrice()) {
            throw new \DomainException(__('Deze aanbieding kan niet worden gekozen.'));
        }
        $provider = $offer->provider;

        DB::transaction(function () use ($job, $offer, $provider, $data) {
            $offer->forceFill(['status' => 'chosen'])->save();
            $job->offers()->whereKeyNot($offer->id)->whereIn('status', ['sent', 'responded'])->update(['status' => 'rejected']);

            $protect = $this->protect->enabled() && $provider->protectReady() && $offer->price_type === 'fixed';
            $job->forceFill([
                'status' => $protect ? 'agreed' : 'scheduled',
                'chosen_offer_id' => $offer->id,
                'provider_id' => $provider->id,
                'agreed_amount' => $offer->price,
                'agreed_type' => $offer->price_type,
                'agreed_scope' => $offer->remarks,
                'agreed_at' => now(),
                'scheduled_at' => $offer->available_at,
                'scheduled_window' => $offer->available_note,
                'warranty_months' => $provider->warranty_months,
                'protect' => $protect,
                'address_line' => mb_substr(trim((string) ($data['address_line'] ?? '')), 0, 200) ?: $job->address_line,
                'customer_phone' => $data['customer_phone'] ?? $job->customer_phone,
            ])->save();
        });

        $job->refresh();
        $this->log($job, 'chosen', 'customer', __('Klant koos :provider voor :amount', ['provider' => $provider->display_name, 'amount' => Fachora::money($job->agreed_amount)]));
        $this->notifier->provider($provider, 'chosen', ['note' => $data['note'] ?? null], $job, $offer);
        foreach ($job->offers()->where('status', 'rejected')->with('provider')->get() as $other) {
            $this->notifier->provider($other->provider, 'rejected', [], $job, $other);
        }
        $this->notifier->customer($job, 'agreed');
    }

    /** Checkout-adres voor de reservering (Protect). */
    public function checkoutUrl(ServiceJob $job): string
    {
        if ($job->status !== 'agreed' || ! $job->protect) {
            throw new \DomainException(__('Deze opdracht hoeft niet (meer) te worden gereserveerd.'));
        }

        return $this->protect->checkoutUrl($job, route('fachora.job.paid', $job->token), $job->url());
    }

    /** De reservering is binnen (webhook of terugkeerpagina). */
    public function markPaid(ServiceJob $job): void
    {
        if ($job->status !== 'agreed') {
            return;
        }
        $job->forceFill(['status' => 'scheduled'])->save();
        $this->log($job, 'reserved', 'customer', __('Betaling gereserveerd: :amount', ['amount' => Fachora::money($job->agreed_amount)]));
        $this->notifier->customer($job, 'reserved');
        if ($job->provider) {
            $this->notifier->provider($job->provider, 'reserved', [], $job);
        }
    }

    /** Terugkeer van Stripe: zonder webhook alsnog de sessie nakijken. */
    public function syncPayment(ServiceJob $job): void
    {
        if ($job->paid_at !== null || blank($job->stripe_session_id)) {
            return;
        }
        $session = $this->protect->retrieveSession($job->stripe_session_id);
        if ($session && $this->protect->completeSession($session)) {
            $this->markPaid($job->fresh());
        }
    }

    public function start(ServiceJob $job): void
    {
        if ($job->status !== 'scheduled') {
            throw new \DomainException(__('Alleen een ingeplande opdracht kan starten.'));
        }
        $job->forceFill(['status' => 'in_progress', 'started_at' => now()])->save();
        $this->log($job, 'started', 'provider', __('Werk gestart'));
    }

    public function proposeExtra(ServiceJob $job, string $description, float $amount): ServiceJobExtra
    {
        if (! in_array($job->status, ['scheduled', 'in_progress'], true)) {
            throw new \DomainException(__('Meerwerk kan alleen tijdens de uitvoering worden voorgesteld.'));
        }
        $extra = $job->extras()->create(['description' => mb_substr($description, 0, 200), 'amount' => round($amount, 2), 'status' => 'proposed']);
        $this->log($job, 'extra_proposed', 'provider', __('Meerwerk voorgesteld: :desc (:amount)', ['desc' => $extra->description, 'amount' => Fachora::money($extra->amount)]));
        $this->notifier->customer($job, 'extra', ['extra_id' => $extra->id]);

        return $extra;
    }

    public function decideExtra(ServiceJobExtra $extra, bool $approve): void
    {
        if ($extra->status !== 'proposed') {
            throw new \DomainException(__('Dit meerwerk is al beoordeeld.'));
        }
        $extra->forceFill(['status' => $approve ? 'approved' : 'declined', 'decided_at' => now()])->save();
        $job = $extra->job;
        $this->log($job, 'extra_decided', 'customer', $approve ? __('Meerwerk goedgekeurd: :desc', ['desc' => $extra->description]) : __('Meerwerk afgewezen: :desc', ['desc' => $extra->description]));
        if ($job->provider) {
            $this->notifier->provider($job->provider, 'extra_decided', ['approved' => $approve, 'extra_id' => $extra->id], $job);
        }
    }

    /** @param  list<UploadedFile>  $files */
    public function done(ServiceJob $job, ?string $note, array $files = []): void
    {
        if (! in_array($job->status, ['scheduled', 'in_progress'], true)) {
            throw new \DomainException(__('Deze opdracht kan niet als klaar worden gemeld.'));
        }
        foreach ($files as $file) {
            FachoraFile::fromUpload('job', $job->id, 'done', $file);
        }
        $job->forceFill(['status' => 'done', 'done_at' => now(), 'done_note' => $note ? mb_substr($note, 0, 2000) : null, 'started_at' => $job->started_at ?: now()])->save();
        $this->log($job, 'done', 'provider', __('Werk gemeld als klaar'));
        $this->notifier->customer($job, 'done');
    }

    /* ---------- akkoord, geschil, afronding ---------- */

    /** De klant (of de termijn, of de eigenaar) keurt het werk goed: uitbetalen, factureren, beoordelen. */
    public function accept(ServiceJob $job, string $by = 'customer'): void
    {
        if (! in_array($job->status, ['done', 'disputed', 'in_progress', 'scheduled'], true) || $job->provider === null) {
            throw new \DomainException(__('Deze opdracht kan niet worden goedgekeurd.'));
        }
        $provider = $job->provider;
        $feePercent = $provider->feePercent();
        $base = $job->isPaid() ? (float) $job->agreed_amount : $job->totalAmount();
        $fee = round($base * $feePercent / 100, 2);

        $job->forceFill([
            'status' => 'accepted',
            'accepted_at' => now(),
            'accepted_by' => $by,
            'fee_percent' => $feePercent,
            'fee_amount' => $fee,
            'payout_amount' => $job->isPaid() ? round((float) $job->agreed_amount - $fee, 2) : null,
            'resolved_at' => $job->status === 'disputed' ? now() : $job->resolved_at,
        ])->save();
        $this->log($job, 'accepted', $by === 'auto' ? 'system' : $by, $by === 'auto' ? __('Stilzwijgend goedgekeurd na :n dagen', ['n' => (int) config('fachora.auto_accept_days')]) : __('Werk goedgekeurd'));

        if ($job->isPaid()) {
            try {
                $this->protect->payout($job);
                $this->log($job, 'payout', 'system', __('Uitbetaald aan :provider: :amount (fee :fee)', ['provider' => $provider->display_name, 'amount' => Fachora::money($job->payout_amount), 'fee' => Fachora::money($fee)]));
            } catch (\Throwable $e) {
                Log::error('Fachora: uitbetaling mislukt', ['job' => $job->id, 'error' => $e->getMessage()]);
                $this->log($job, 'payout_failed', 'system', __('Uitbetaling mislukt: :error', ['error' => mb_substr($e->getMessage(), 0, 200)]));
                $this->notifier->owner('payout_failed', ['error' => $e->getMessage()], $job);
            }
        }

        $this->draftInvoice($job);
        $provider->increment('jobs_done');
        $this->notifier->provider($provider, 'accepted', [], $job);
        $this->notifier->customer($job, 'accepted');
    }

    /** Conceptfactuur in de administratie van het bedrijf (Lopra-motor), KSeF-klaar. Mag mislukken. */
    private function draftInvoice(ServiceJob $job): void
    {
        $company = $job->provider?->company;
        if (! $company || $job->invoice_id) {
            return;
        }
        try {
            $customer = Customer::withoutGlobalScope('company')->where('company_id', $company->id)
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($job->customer_email)])->first()
                ?: Customer::create([
                    'company_id' => $company->id,
                    'name' => $job->customer_name,
                    'type' => 'consumer',
                    'email' => $job->customer_email,
                    'phone' => $job->customer_phone,
                    'address_line' => $job->address_line,
                    'postal_code' => $job->postal_code,
                    'city' => $job->city,
                    'country' => 'PL',
                    'language' => $job->customer_locale === 'en' ? 'en' : 'pl',
                    'payment_terms' => 14,
                ]);

            $lines = [['description' => $job->reference . ' · ' . $job->title, 'quantity' => 1, 'unit_price' => (float) $job->agreed_amount, 'vat_rate' => 23]];
            foreach ($job->extras()->where('status', 'approved')->get() as $extra) {
                $lines[] = ['description' => $extra->description, 'quantity' => 1, 'unit_price' => (float) $extra->amount, 'vat_rate' => 23];
            }
            $invoice = app(InvoiceManager::class)->create([
                'customer_id' => $customer->id,
                'price_mode' => 'incl',
                'reference' => 'Fachora ' . $job->reference,
                'language' => $customer->language,
                'lines' => $lines,
                'notes' => $job->isPaid() ? __('Betaald via Fachora Protect; het bedrag is al voldaan.') : null,
            ]);
            $job->forceFill(['invoice_id' => $invoice->id])->save();
            $this->log($job, 'invoice', 'system', __('Conceptfactuur klaargezet in de administratie van het bedrijf'));
        } catch (\Throwable $e) {
            Log::warning('Fachora: conceptfactuur mislukt', ['job' => $job->id, 'error' => $e->getMessage()]);
        }
    }

    /** @param  list<UploadedFile>  $files */
    public function dispute(ServiceJob $job, string $reason, array $files = []): void
    {
        if (! in_array($job->status, ['done', 'in_progress', 'scheduled'], true)) {
            throw new \DomainException(__('Een geschil kan alleen worden geopend over werk dat is gemeld of gestart.'));
        }
        foreach ($files as $file) {
            FachoraFile::fromUpload('job', $job->id, 'dispute', $file);
        }
        $job->forceFill(['status' => 'disputed', 'dispute_opened_at' => now(), 'dispute_reason' => mb_substr($reason, 0, 3000)])->save();
        $this->log($job, 'disputed', 'customer', __('Geschil geopend'));
        if ($job->provider) {
            $this->notifier->provider($job->provider, 'dispute', [], $job);
        }
        $this->notifier->customer($job, 'dispute_opened');
        $this->notifier->owner('dispute', [], $job);
    }

    public function replyDispute(ServiceJob $job, string $text): void
    {
        if ($job->status !== 'disputed') {
            throw new \DomainException(__('Er is geen open geschil.'));
        }
        $job->forceFill(['dispute_reply' => mb_substr($text, 0, 3000), 'dispute_replied_at' => now()])->save();
        $this->log($job, 'dispute_reply', 'provider', __('Bedrijf reageerde op het geschil'));
        $this->notifier->customer($job, 'dispute_reply');
        $this->notifier->owner('dispute_reply', [], $job);
    }

    /**
     * De eigenaar beslist: alles uitbetalen, alles terugbetalen, of een deel
     * (payoutAmount gaat naar het bedrijf, de rest terug naar de klant).
     */
    public function resolve(ServiceJob $job, string $resolution, ?float $payoutAmount, ?string $note, string $byName): void
    {
        if ($job->status !== 'disputed') {
            throw new \DomainException(__('Er is geen open geschil.'));
        }
        if (! in_array($resolution, ['payout', 'refund', 'split'], true)) {
            throw new \DomainException('Onbekende uitkomst.');
        }
        $job->forceFill(['dispute_resolution' => $resolution, 'dispute_note' => $note ? mb_substr($note, 0, 3000) : null])->save();

        if ($resolution === 'payout') {
            $this->accept($job, 'owner');
        } elseif ($resolution === 'refund') {
            if ($job->isPaid()) {
                $this->protect->refund($job);
            }
            $job->forceFill(['status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => 'owner', 'cancel_reason' => $note, 'resolved_at' => now()])->save();
            $this->log($job, 'resolved', 'owner', __('Geschil beslist door :name: volledige terugbetaling', ['name' => $byName]));
        } else {
            $amount = max(0.0, min((float) $job->agreed_amount, (float) $payoutAmount));
            $fee = round($amount * $job->provider->feePercent() / 100, 2);
            if ($job->isPaid()) {
                $refund = round((float) $job->agreed_amount - $amount, 2);
                if ($refund > 0) {
                    $this->protect->refund($job, $refund);
                }
                $job->forceFill(['payout_amount' => round($amount - $fee, 2)])->save();
                $this->protect->payout($job);
            }
            $job->forceFill([
                'status' => 'accepted', 'accepted_at' => now(), 'accepted_by' => 'owner', 'resolved_at' => now(),
                'fee_percent' => $job->provider->feePercent(), 'fee_amount' => $fee, 'payout_amount' => round($amount - $fee, 2),
            ])->save();
            $job->provider->increment('jobs_done');
            $this->log($job, 'resolved', 'owner', __('Geschil beslist door :name: :amount aan het bedrijf, rest terug naar de klant', ['name' => $byName, 'amount' => Fachora::money($amount)]));
        }

        $job->refresh();
        $this->notifier->customer($job, 'resolved', ['resolution' => $resolution, 'note' => $note]);
        if ($job->provider) {
            $this->notifier->provider($job->provider, 'resolved', ['resolution' => $resolution, 'note' => $note], $job);
        }
    }

    public function cancel(ServiceJob $job, string $by, ?string $reason = null): void
    {
        if ($job->isClosed() || in_array($job->status, ['done', 'disputed'], true)) {
            throw new \DomainException(__('Deze opdracht kan niet meer worden geannuleerd.'));
        }

        // Klant zegt kort voor de afspraak af (binnen cancel_free_hours) terwijl het bedrag
        // gereserveerd is: het bedrijf houdt de voorrijkosten uit zijn aanbieding, zonder fee.
        $freeHours = (int) config('fachora.cancel_free_hours', 24);
        $callout = 0.0;
        if ($by === 'customer' && $job->isPaid() && $job->provider && $job->scheduled_at && $job->scheduled_at->lt(now()->addHours($freeHours))) {
            $callout = min((float) $job->agreed_amount, round((float) ($job->chosenOffer?->callout ?? ($job->provider->rates['callout'] ?? 0)), 2));
        }

        if ($job->isPaid()) {
            if ($callout > 0) {
                $this->protect->refund($job, round((float) $job->agreed_amount - $callout, 2));
                $job->forceFill(['payout_amount' => $callout, 'fee_percent' => 0, 'fee_amount' => 0])->save();
                try {
                    $this->protect->payout($job);
                } catch (\Throwable $e) {
                    Log::error('Fachora: uitbetaling voorrijkosten mislukt', ['job' => $job->id, 'error' => $e->getMessage()]);
                    $this->notifier->owner('payout_failed', ['error' => $e->getMessage()], $job);
                }
                $this->log($job, 'callout', 'system', __('Voorrijkosten ingehouden: :amount (annulering binnen :hours uur)', ['amount' => Fachora::money($callout), 'hours' => $freeHours]));
            } else {
                $this->protect->refund($job);
            }
        }

        $job->offers()->whereIn('status', ['sent', 'responded', 'chosen'])->update(['status' => 'expired']);
        $job->forceFill(['status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => $by, 'cancel_reason' => $reason ? mb_substr($reason, 0, 1000) : null])->save();
        $this->log($job, 'cancelled', $by, __('Geannuleerd'));
        if ($job->provider && $by !== 'provider') {
            $this->notifier->provider($job->provider, 'cancelled', ['reason' => $reason, 'callout' => $callout], $job);
        }
        if ($by !== 'customer') {
            $this->notifier->customer($job, 'cancelled', ['reason' => $reason]);
        } elseif ($callout > 0) {
            $this->notifier->customer($job, 'cancelled', ['reason' => $reason, 'callout' => $callout, 'hours' => $freeHours]);
        }
    }

    public function review(ServiceJob $job, int $rating, ?string $text): ServiceReview
    {
        if ($job->status !== 'accepted' || $job->provider === null) {
            throw new \DomainException(__('Beoordelen kan pas na een afgeronde opdracht.'));
        }
        if ($job->review()->exists()) {
            throw new \DomainException(__('Deze opdracht is al beoordeeld.'));
        }
        $review = $job->review()->create(['provider_id' => $job->provider_id, 'rating' => max(1, min(5, $rating)), 'text' => $text ? mb_substr($text, 0, 2000) : null]);
        $job->forceFill(['reviewed_at' => now()])->save();
        $this->providers->recalculateRating($job->provider);
        $this->log($job, 'reviewed', 'customer', __('Beoordeeld met :n sterren', ['n' => $review->rating]));

        return $review;
    }

    public function replyReview(ServiceReview $review, string $text): void
    {
        $review->forceFill(['reply' => mb_substr($text, 0, 2000), 'replied_at' => now()])->save();
    }

    /* ---------- planner ---------- */

    /** Eén herinnering aan bedrijven die na een dag nog niet reageerden. */
    public function remindOffers(): int
    {
        $n = 0;
        $due = ServiceJobOffer::query()->where('status', 'sent')->whereNull('reminded_at')
            ->where('sent_at', '<=', now()->subHours((int) config('fachora.remind_hours', 24)))
            ->whereHas('job', fn ($q) => $q->whereIn('status', ['open', 'quoted']))
            ->with(['job', 'provider'])->get();
        foreach ($due as $offer) {
            if ($this->notifier->provider($offer->provider, 'reminder', [], $offer->job, $offer)) {
                $offer->forceFill(['reminded_at' => now()])->save();
                $n++;
            }
        }

        return $n;
    }

    /** Na de reactietermijn: zonder prijs een volgende ronde, met prijs blijft de klant kiezen. */
    public function expireOffers(): int
    {
        $n = 0;
        $jobs = ServiceJob::query()->whereIn('status', ['open', 'quoted'])->whereNotNull('offers_deadline_at')->where('offers_deadline_at', '<=', now())->get();
        foreach ($jobs as $job) {
            $job->offers()->where('status', 'sent')->update(['status' => 'expired']);
            $n++;
            if ($job->offers()->where('status', 'responded')->exists()) {
                // Er ligt een prijs: de klant kiest op zijn gemak, er komt geen nieuwe ronde.
                $job->forceFill(['offers_deadline_at' => null])->save();
                continue;
            }
            if ($job->waves >= 2) {
                $job->forceFill(['status' => 'no_match', 'offers_deadline_at' => null])->save();
                $this->log($job, 'no_match', 'system', __('Geen reactie na twee rondes; de eigenaar is ingelicht'));
                $this->notifier->owner('no_match', [], $job);
                $this->notifier->customer($job, 'no_match');
            } else {
                $this->dispatch($job->fresh());
            }
        }

        return $n;
    }

    /** Stilzwijgende goedkeuring na "klaar" zonder reactie van de klant. */
    public function autoAccept(): int
    {
        $n = 0;
        $due = ServiceJob::query()->where('status', 'done')->where('done_at', '<=', now()->subDays((int) config('fachora.auto_accept_days', 5)))->get();
        foreach ($due as $job) {
            try {
                $this->accept($job, 'auto');
                $n++;
            } catch (\Throwable $e) {
                Log::warning('Fachora: automatisch goedkeuren mislukt', ['job' => $job->id, 'error' => $e->getMessage()]);
            }
        }

        return $n;
    }

    public function purgeUnconfirmed(): int
    {
        $old = ServiceJob::query()->where('status', 'pending')->where('created_at', '<', now()->subDays((int) config('fachora.confirm_days', 7)))->get();
        foreach ($old as $job) {
            FachoraFile::query()->where('owner_type', 'job')->where('owner_id', $job->id)->delete();
            $job->delete();
        }

        return $old->count();
    }

    /** Aanvragen waarbij de klant na een week nog geen bedrijven koos (of niet betaalde): verlopen. */
    public function expireSelecting(): int
    {
        $old = ServiceJob::query()->where('status', 'selecting')->whereNull('request_paid_at')
            ->where('updated_at', '<', now()->subDays((int) config('fachora.confirm_days', 7)))->get();
        foreach ($old as $job) {
            $job->forceFill(['status' => 'expired'])->save();
            $this->log($job, 'expired', 'system', __('Geen bedrijven gekozen; aanvraag verlopen'));
        }

        return $old->count();
    }

    /* ---------- hulp ---------- */

    public function log(ServiceJob $job, string $kind, string $actor, string $description, array $data = []): ServiceJobEvent
    {
        return $job->events()->create(['kind' => $kind, 'actor' => $actor, 'description' => $description, 'data' => $data ?: null, 'created_at' => now()]);
    }

    public function findByToken(?string $token): ?ServiceJob
    {
        return is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token) ? ServiceJob::query()->where('token', $token)->first() : null;
    }

    public function findOfferByToken(?string $token): ?ServiceJobOffer
    {
        return is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token) ? ServiceJobOffer::query()->where('token', $token)->with(['job', 'provider'])->first() : null;
    }
}
