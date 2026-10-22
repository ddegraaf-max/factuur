<?php

namespace App\Services\Fachora;

use App\Models\Company;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Models\ServiceReview;
use App\Models\User;
use App\Services\NipService;
use App\Support\Fachora;
use App\Support\Market;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Bedrijven op Fachora: de database vullen (register, import, aanmelding),
 * het profiel laten claimen, het profiel bijhouden en bedrijven zoeken bij
 * een opdracht. Verificatie staat apart in VerificationService.
 */
class ProviderService
{
    public const CLAIM_LIMIT_HOUR = 5;

    public function __construct(private GeocodeService $geo, private Notifier $notifier) {}

    /* ---------- database vullen ---------- */

    /**
     * Bedrijven toevoegen uit een lijst (CEIDG-export, gekochte opt-in-lijst,
     * eigen plaklijst). Bestaande NIP of dezelfde naam+plaats wordt overgeslagen.
     * Alleen rijen met 'consent' => true krijgen consent_at (met de bron erbij):
     * die mogen een uitnodiging krijgen, de rest alleen per post.
     *
     * @param  list<array<string, mixed>>  $records
     * @return array{created: int, skipped: int, consented: int}
     */
    public function importRecords(array $records, string $source = 'import', ?string $consentSource = null): array
    {
        $created = $skipped = $consented = 0;

        foreach ($records as $record) {
            $name = trim((string) ($record['name'] ?? ''));
            if ($name === '') {
                $skipped++;
                continue;
            }
            $nip = NipService::normalize($record['nip'] ?? null) ?: null;
            if ($nip !== null && ! NipService::valid($nip)) {
                $nip = null;
            }
            $city = trim((string) ($record['city'] ?? '')) ?: null;

            $exists = Provider::query()
                ->when($nip, fn ($q) => $q->where('nip', $nip))
                ->when(! $nip, fn ($q) => $q->whereRaw('LOWER(display_name) = ?', [mb_strtolower($name)])->where('city', $city))
                ->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            $consent = filter_var($record['consent'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $provider = $this->createFromRegister([
                'nip' => $nip,
                'regon' => $record['regon'] ?? null,
                'legal_name' => $record['legal_name'] ?? $name,
                'display_name' => $name,
                'legal_form' => $record['legal_form'] ?? null,
                'address_line' => $record['address'] ?? null,
                'postal_code' => $record['postal_code'] ?? null,
                'city' => $city,
                'voivodeship' => $record['voivodeship'] ?? null,
                'lat' => isset($record['lat']) ? (float) $record['lat'] : null,
                'lng' => isset($record['lng']) ? (float) $record['lng'] : null,
                'phone' => $record['phone'] ?? null,
                'email' => $record['email'] ?? null,
                'website' => $record['website'] ?? null,
                'source' => $record['source'] ?? $source,
                'consent_at' => $consent ? now() : null,
                'consent_source' => $consent ? ($consentSource ?: $source) : null,
                'notes' => $record['note'] ?? null,
            ], (array) ($record['categories'] ?? []));

            $created++;
            if ($provider->consent_at) {
                $consented++;
            }
        }

        return compact('created', 'skipped', 'consented');
    }

    /**
     * Plaklijst: één bedrijf per regel, velden gescheiden door puntkomma's:
     * nazwa; nip; e-mail; telefon; miasto; kod pocztowy; kategorie (komma's); zgoda (tak/nie)
     *
     * @return array{created: int, skipped: int, consented: int}
     */
    public function importLines(string $text, string $source = 'import', ?string $consentSource = null): array
    {
        $records = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $i => $line) {
            $line = trim($line);
            if ($line === '' || ($i === 0 && preg_match('/^(nazwa|naam|name)\b/i', $line))) {
                continue;
            }
            $parts = array_map('trim', explode(';', $line));
            $records[] = [
                'name' => $parts[0] ?? '',
                'nip' => $parts[1] ?? null,
                'email' => filter_var($parts[2] ?? '', FILTER_VALIDATE_EMAIL) ? $parts[2] : null,
                'phone' => $parts[3] ?? null,
                'city' => $parts[4] ?? null,
                'postal_code' => $parts[5] ?? null,
                'categories' => array_filter(array_map(fn ($c) => Str::slug(trim($c)), explode(',', $parts[6] ?? ''))),
                'consent' => in_array(mb_strtolower($parts[7] ?? ''), ['tak', 'ja', 'yes', '1', 'true'], true),
            ];
        }

        return $this->importRecords($records, $source, $consentSource);
    }

    /**
     * Eén bedrijf uit een register of lijst (status unclaimed).
     *
     * @param  array<string, mixed>  $attrs
     * @param  list<string>  $categories
     */
    public function createFromRegister(array $attrs, array $categories = []): Provider
    {
        $name = (string) ($attrs['display_name'] ?? $attrs['legal_name'] ?? 'Firma');
        $provider = Provider::create(array_merge($attrs, [
            'display_name' => mb_substr($name, 0, 160),
            'legal_name' => mb_substr((string) ($attrs['legal_name'] ?? $name), 0, 200),
            'slug' => Provider::makeSlug($name, $attrs['city'] ?? null),
            'status' => 'unclaimed',
            'source_fetched_at' => $attrs['source_fetched_at'] ?? now(),
            'optout_token' => bin2hex(random_bytes(32)),
        ]));
        $provider->syncCategories($categories);
        $this->geocode($provider);

        return $provider;
    }

    /** Coördinaten erbij als die ontbreken (zonder de aanroeper te breken). */
    public function geocode(Provider $provider): void
    {
        if ($provider->lat !== null && $provider->lng !== null) {
            return;
        }
        $hit = $this->geo->lookup($provider->postal_code, $provider->city);
        if ($hit) {
            $provider->forceFill($hit)->save();
        }
    }

    /** Bedrijven zonder coördinaten alsnog geocoderen (planner, in porties). */
    public function geocodePending(int $limit = 30): int
    {
        $done = 0;
        foreach (Provider::query()->whereNull('lat')->where(fn ($q) => $q->whereNotNull('city')->orWhereNotNull('postal_code'))->orderBy('id')->limit($limit)->get() as $provider) {
            $this->geocode($provider);
            $done += $provider->lat !== null ? 1 : 0;
        }

        return $done;
    }

    /* ---------- claimen ---------- */

    /**
     * Een bedrijf dat nog niet in de database staat meldt zichzelf aan
     * (/dla-firm/zglos). Komt meteen in de claimflow terecht.
     *
     * @param  array<string, mixed>  $data
     */
    public function createApplication(array $data, Request $request): Provider
    {
        $nip = NipService::normalize($data['nip'] ?? null);
        $existing = $nip ? Provider::query()->where('nip', $nip)->first() : null;
        if ($existing && $existing->claimed_at) {
            throw new \DomainException(__('Dit bedrijf heeft al een profiel op Fachora. Log in, of vraag een nieuw wachtwoord aan.'));
        }

        $facts = null;
        if ($nip) {
            try {
                $facts = app(NipService::class)->lookup($nip);
            } catch (\Throwable) {
                $facts = null;
            }
        }

        $provider = $existing ?: $this->createFromRegister([
            'nip' => $nip ?: null,
            'regon' => $facts['regon'] ?? null,
            'legal_name' => $facts['name'] ?? $data['company_name'],
            'display_name' => $data['company_name'],
            'legal_form' => $data['legal_form'] ?? null,
            'address_line' => $data['address'] ?? ($facts['address'] ?? null),
            'postal_code' => $data['postal_code'] ?? ($facts['postal_code'] ?? null),
            'city' => $data['city'] ?? ($facts['city'] ?? null),
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'source' => 'manual',
            'vat_status' => self::vatStatus($facts['vat_status'] ?? null),
            'vat_checked_at' => $facts ? now() : null,
        ], (array) ($data['categories'] ?? []));

        return $this->startClaim($provider, ['email' => $data['email'], 'phone' => $data['phone'] ?? null, 'name' => $data['name']], $request);
    }

    public function mayClaim(Request $request): bool
    {
        return ! RateLimiter::tooManyAttempts('fachora-claim:' . $request->ip(), self::CLAIM_LIMIT_HOUR);
    }

    /**
     * Stap 1 van het claimen: de persoon vult zijn e-mailadres in; daar gaat
     * de bevestigingslink heen. Niets verandert aan het profiel totdat die
     * link is gebruikt.
     *
     * @param  array{email: string, phone?: ?string, name: string}  $data
     */
    public function startClaim(Provider $provider, array $data, Request $request): Provider
    {
        if ($provider->claimed_at !== null) {
            throw new \DomainException(__('Dit profiel is al geclaimd. Log in met het e-mailadres van de eigenaar.'));
        }
        if ($provider->status === 'blocked') {
            throw new \DomainException(__('Dit bedrijf kan geen profiel claimen. Neem contact met ons op.'));
        }

        $provider->forceFill([
            'claim_token' => bin2hex(random_bytes(32)),
            'claim_email' => mb_strtolower(trim($data['email'])),
            'claim_phone' => $data['phone'] ?? null,
            'claim_name' => mb_substr(trim($data['name']), 0, 120),
            'claim_requested_at' => now(),
        ])->save();

        RateLimiter::hit('fachora-claim:' . $request->ip(), 3600);
        $this->notifier->claimant($provider, 'claim', $provider->claim_email);

        return $provider;
    }

    public function findByClaimCode(?string $code): ?Provider
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code) ?? '');

        return $code === '' ? null : Provider::query()->where('claim_code', $code)->first();
    }

    public function findByClaimToken(?string $token): ?Provider
    {
        if (! is_string($token) || ! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $provider = Provider::query()->where('claim_token', $token)->first();
        if (! $provider || $provider->claimed_at !== null || ! $provider->claim_requested_at || $provider->claim_requested_at->lt(now()->subDays(7))) {
            return null;
        }

        return $provider;
    }

    /**
     * Stap 2: de link uit de mail is gebruikt. Er ontstaat een inlog en een
     * eigen administratie in de markt pl; het profiel wordt 'claimed' en het
     * bedrijf heeft daarmee zelf om berichten van Fachora gevraagd (consent).
     */
    public function confirmClaim(Provider $provider, string $password): User
    {
        $email = (string) $provider->claim_email;

        return DB::transaction(function () use ($provider, $password, $email) {
            $user = User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();

            if (! $user) {
                $company = $this->createCompany($provider, $email);
                $user = User::create([
                    'name' => $provider->claim_name ?: $provider->display_name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'company_id' => $company->id,
                    'locale' => app()->getLocale() !== Market::locale() ? app()->getLocale() : null,
                ]);
                $user->companies()->attach($company->id, ['role' => 'owner']);
                $user->forceFill(['email_verified_at' => now()])->save();
            } else {
                $company = $user->company ?: $this->createCompany($provider, $email);
                if (! $user->companies()->whereKey($company->id)->exists()) {
                    $user->companies()->attach($company->id, ['role' => 'owner']);
                }
            }

            $provider->forceFill([
                'status' => 'claimed',
                'claimed_at' => now(),
                'consent_at' => $provider->consent_at ?: now(),
                'consent_source' => $provider->consent_source ?: 'claim',
                'email' => $email,
                'email_verified_at' => now(),
                'phone' => $provider->claim_phone ?: $provider->phone,
                'user_id' => $user->id,
                'company_id' => $company->id,
                'claim_token' => null,
                'source' => $provider->source === 'manual' ? 'claimed' : $provider->source,
                'optout_token' => $provider->optout_token ?: bin2hex(random_bytes(32)),
            ])->save();

            app(VerificationService::class)->runAutomatic($provider);

            return $user;
        });
    }

    private function createCompany(Provider $provider, string $email): Company
    {
        return Company::create([
            'name' => $provider->legal_name ?: $provider->display_name,
            'trading_name' => $provider->display_name !== $provider->legal_name ? $provider->display_name : null,
            'vat_number' => $provider->nip,
            'kvk_number' => $provider->regon,
            'email' => $email,
            'phone' => $provider->claim_phone ?: $provider->phone,
            'website' => $provider->website,
            'address_line' => $provider->address_line,
            'postal_code' => $provider->postal_code,
            'city' => $provider->city,
            'country' => 'PL',
            'currency' => 'PLN',
            'brand_color' => (string) brand('color', '#0B2545'),
            'default_payment_terms' => 14,
            'invoice_number_format' => 'FV/{year}/{sequence:4}',
            'invoice_footer' => (string) Market::get('invoice_footer'),
            'invoice_template' => 'modern',
            'invoice_font' => 'sans',
            'price_mode' => 'incl',
            'fiscal_year_start' => 1,
            'default_send_method' => 'email',
            'results_per_page' => 25,
            'daily_notification_enabled' => false,
            'daily_notification_email' => $email,
            // Zolang het bedrijf op Fachora staat is de administratie gratis; bij verificatie wordt dit verlengd.
            'trial_ends_at' => now()->addYear(),
        ]);
    }

    /** Afmelden voor e-mail via de link onderaan elke mail aan een bedrijf. */
    public function blockEmail(string $token): ?Provider
    {
        $provider = Provider::query()->where('optout_token', $token)->first();
        if ($provider && $provider->email_blocked_at === null) {
            $provider->forceFill(['email_blocked_at' => now()])->save();
        }

        return $provider;
    }

    /* ---------- profiel ---------- */

    /** @param  array<string, mixed>  $data */
    public function updateProfile(Provider $provider, array $data): void
    {
        $languages = [];
        foreach ((array) ($data['languages'] ?? []) as $code => $level) {
            if (Fachora::isLanguage((string) $code) && isset(config('fachora.levels')[$level])) {
                $languages[$code] = $level;
            }
        }
        $languages = ['pl' => $languages['pl'] ?? 'native'] + $languages;

        $rates = array_filter([
            'callout' => isset($data['rates']['callout']) && $data['rates']['callout'] !== '' ? round((float) $data['rates']['callout'], 2) : null,
            'first_hour' => isset($data['rates']['first_hour']) && $data['rates']['first_hour'] !== '' ? round((float) $data['rates']['first_hour'], 2) : null,
            'extra_hour' => isset($data['rates']['extra_hour']) && $data['rates']['extra_hour'] !== '' ? round((float) $data['rates']['extra_hour'], 2) : null,
        ], fn ($v) => $v !== null);

        $provider->fill([
            'display_name' => $data['display_name'] ?? $provider->display_name,
            'description' => $data['description'] ?? $provider->description,
            'phone' => $data['phone'] ?? $provider->phone,
            'website' => $data['website'] ?? $provider->website,
            'address_line' => $data['address_line'] ?? $provider->address_line,
            'postal_code' => $data['postal_code'] ?? $provider->postal_code,
            'city' => $data['city'] ?? $provider->city,
            'languages' => $languages,
            'radius_km' => max(5, min(200, (int) ($data['radius_km'] ?? $provider->radius_km))),
            'rates' => $rates ?: null,
            'warranty_months' => max(0, min(60, (int) ($data['warranty_months'] ?? $provider->warranty_months))),
            'invoice_promise' => filter_var($data['invoice_promise'] ?? $provider->invoice_promise, FILTER_VALIDATE_BOOLEAN),
            'protect_opt_in' => filter_var($data['protect_opt_in'] ?? $provider->protect_opt_in, FILTER_VALIDATE_BOOLEAN),
        ]);

        if ($provider->isDirty(['city', 'postal_code'])) {
            $provider->lat = null;
            $provider->lng = null;
        }
        if ($provider->isDirty('display_name') || $provider->isDirty('city')) {
            $provider->slug = Provider::makeSlug($provider->display_name, $provider->city, $provider->id);
        }
        $provider->save();

        if (isset($data['categories'])) {
            $provider->syncCategories((array) $data['categories']);
        }
        $this->geocode($provider);
    }

    /* ---------- zoeken bij een opdracht ---------- */

    /**
     * Verified bedrijven voor deze opdracht: categorie, binnen hun eigen
     * werkstraal, bij voorkeur in de taal van de klant; gerangschikt op
     * beoordeling, reactiesnelheid en afstand.
     *
     * @param  list<int>  $exclude
     * @return Collection<int, Provider>
     */
    public function match(ServiceJob $job, int $limit, array $exclude = [], bool $requireLanguage = true): Collection
    {
        // orderBy('id'): bij gelijke beoordeling blijft de volgorde voorspelbaar (Postgres geeft anders willekeur).
        $query = Provider::query()->verified()->inCategory($job->category)
            ->whereNotNull('email')->whereNull('email_blocked_at')
            ->when($exclude, fn ($q) => $q->whereNotIn('id', $exclude))
            ->orderBy('id');

        if ($job->lat !== null && $job->lng !== null) {
            // Grof vangnet van ±1,8° (± 200 km); de echte afstand rekenen we hieronder uit.
            $query->whereNotNull('lat')->whereBetween('lat', [$job->lat - 1.8, $job->lat + 1.8])->whereBetween('lng', [$job->lng - 2.8, $job->lng + 2.8]);
        } else {
            $query->whereRaw('LOWER(city) = ?', [mb_strtolower((string) $job->city)]);
        }

        $candidates = $query->get()->filter(function (Provider $p) use ($job, $requireLanguage) {
            // Alleen bedrijven die wij mogen mailen (geclaimd of opt-in; nooit uit een register zonder toestemming).
            if (! ConsentPolicy::mayEmail($p)) {
                return false;
            }
            if ($requireLanguage && ! $p->speaks($job->language)) {
                return false;
            }
            if ($job->lat !== null && $p->lat !== null) {
                $p->setAttribute('distance_km', round(Fachora::distanceKm($job->lat, $job->lng, $p->lat, $p->lng), 1));

                return $p->distance_km <= max(5, (int) $p->radius_km);
            }
            $p->setAttribute('distance_km', null);

            return true;
        });

        return $candidates->sortBy([
            fn (Provider $a, Provider $b) => ($b->rating_avg ?? 0) <=> ($a->rating_avg ?? 0),
            fn (Provider $a, Provider $b) => ($a->response_minutes ?? PHP_INT_MAX) <=> ($b->response_minutes ?? PHP_INT_MAX),
            fn (Provider $a, Provider $b) => ($a->distance_km ?? 999) <=> ($b->distance_km ?? 999),
        ])->take($limit)->values();
    }

    /**
     * De bedrijven waaruit de klant zelf kiest (1.86): in de categorie, binnen
     * region_radius_km (of de eigen werkstraal als die groter is), met een
     * e-mailadres waarheen een klantaanvraag mag (ConsentPolicy::mayEmailRequest).
     * Verified eerst, dan geclaimd, dan uit het register; daarbinnen op
     * beoordeling en afstand. Bedrijven waarvan het register zegt dat ze niet
     * btw-geregistreerd zijn (vat_status 'none'), vallen af.
     *
     * @return Collection<int, Provider>
     */
    public function choiceCandidates(ServiceJob $job, ?int $limit = null): Collection
    {
        $limit ??= (int) config('fachora.choice_candidates', 15);
        $radius = (int) config('fachora.region_radius_km', 50);
        $query = Provider::query()->inCategory($job->category)
            ->whereIn('status', ['unclaimed', 'claimed', 'pending', 'verified'])
            ->whereNotNull('email')->whereNull('email_blocked_at')
            ->where(fn ($q) => $q->whereNull('vat_status')->orWhere('vat_status', '!=', 'none'))
            ->orderBy('id');

        if ($job->lat !== null && $job->lng !== null) {
            $query->whereNotNull('lat')->whereBetween('lat', [$job->lat - 1.8, $job->lat + 1.8])->whereBetween('lng', [$job->lng - 2.8, $job->lng + 2.8]);
        } else {
            $query->whereRaw('LOWER(city) = ?', [mb_strtolower((string) $job->city)]);
        }

        $rank = ['verified' => 0, 'pending' => 1, 'claimed' => 1, 'unclaimed' => 2];

        return $query->get()->filter(function (Provider $p) use ($job, $radius) {
            if ($p->isDemo() || ! ConsentPolicy::mayEmailRequest($p)) {
                return false;
            }
            if ($job->lat !== null && $p->lat !== null) {
                $p->setAttribute('distance_km', round(Fachora::distanceKm($job->lat, $job->lng, $p->lat, $p->lng), 1));

                return $p->distance_km <= max($radius, (int) $p->radius_km);
            }
            $p->setAttribute('distance_km', null);

            return true;
        })->sortBy([
            fn (Provider $a, Provider $b) => ($rank[$a->status] ?? 9) <=> ($rank[$b->status] ?? 9),
            fn (Provider $a, Provider $b) => ($b->rating_avg ?? 0) <=> ($a->rating_avg ?? 0),
            fn (Provider $a, Provider $b) => ($a->distance_km ?? 999) <=> ($b->distance_km ?? 999),
        ])->take($limit)->values();
    }

    /**
     * Bedrijven in de buurt die nog niet geclaimd zijn: de bellijst voor de
     * eigenaar als er geen Verified bedrijf is. Niet mailen (geen toestemming).
     *
     * @return Collection<int, Provider>
     */
    public function nearbyUnclaimed(ServiceJob $job, int $limit = 10): Collection
    {
        $query = Provider::query()->whereIn('status', ['unclaimed', 'claimed', 'pending'])->inCategory($job->category);
        if ($job->lat !== null && $job->lng !== null) {
            $query->whereNotNull('lat')->whereBetween('lat', [$job->lat - 0.6, $job->lat + 0.6])->whereBetween('lng', [$job->lng - 0.9, $job->lng + 0.9]);
        } else {
            $query->whereRaw('LOWER(city) = ?', [mb_strtolower((string) $job->city)]);
        }

        return $query->get()->each(function (Provider $p) use ($job) {
            $p->setAttribute('distance_km', $job->lat !== null && $p->lat !== null ? round(Fachora::distanceKm($job->lat, $job->lng, $p->lat, $p->lng), 1) : null);
        })->sortBy(fn (Provider $p) => $p->distance_km ?? 999)->take($limit)->values();
    }

    /* ---------- reputatie en status ---------- */

    public function recalculateRating(Provider $provider): void
    {
        $reviews = ServiceReview::query()->where('provider_id', $provider->id)->where('published', true);
        $count = (clone $reviews)->count();
        $provider->forceFill([
            'rating_count' => $count,
            'rating_avg' => $count > 0 ? round((float) (clone $reviews)->avg('rating'), 2) : null,
        ])->save();
    }

    /** Gemiddelde reactietijd op aanbiedingen, in minuten (laatste 20). */
    public function recalculateResponseTime(Provider $provider): void
    {
        $rows = $provider->offers()->whereNotNull('responded_at')->whereNotNull('sent_at')->latest('responded_at')->limit(20)->get();
        $provider->forceFill([
            'response_minutes' => $rows->isEmpty() ? null : (int) round($rows->avg(fn ($o) => $o->sent_at->diffInMinutes($o->responded_at))),
        ])->save();
    }

    /** @return array<string, int> */
    public function statusCounts(): array
    {
        $counts = Provider::query()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status')->all();

        return ['all' => array_sum($counts)] + array_map('intval', array_merge(array_fill_keys(Provider::STATUSES, 0), $counts));
    }

    public static function vatStatus(?string $raw): ?string
    {
        return match (mb_strtolower((string) $raw)) {
            'czynny' => 'active',
            'zwolniony' => 'exempt',
            'niezarejestrowany' => 'none',
            '' => null,
            default => 'unknown',
        };
    }
}
