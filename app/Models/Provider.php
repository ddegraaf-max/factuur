<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Een bedrijf op Fachora: uit een openbaar register (unclaimed), geclaimd
 * door de eigenaar, in verificatie, Fachora Verified, geschorst of geblokkeerd.
 * Platformbreed, dus bewust zonder company-scope.
 *
 * @property float|null $distance_km  afstand tot een opdracht, gezet door ProviderService::match()
 */
class Provider extends Model
{
    public const STATUSES = ['unclaimed', 'claimed', 'pending', 'verified', 'suspended', 'blocked'];

    /** Verificatiestappen; 'bank' = Stripe Connect (identiteit + rekening), 'insurance' is optioneel. */
    public const CHECKS = ['registry', 'vat', 'email', 'phone', 'terms', 'bank', 'insurance'];

    public const REQUIRED_CHECKS = ['registry', 'email', 'phone', 'terms'];

    protected $fillable = [
        'nip', 'regon', 'krs', 'legal_name', 'display_name', 'slug', 'legal_form',
        'address_line', 'postal_code', 'city', 'voivodeship', 'lat', 'lng',
        'phone', 'email', 'website', 'source', 'source_fetched_at', 'registry_status', 'vat_status', 'vat_checked_at',
        'status', 'status_reason', 'languages', 'radius_km', 'rates', 'warranty_months', 'invoice_promise', 'protect_opt_in',
        'description', 'photo_data', 'company_id', 'user_id',
        'claim_code', 'claim_token', 'claim_email', 'claim_phone', 'claim_name', 'claim_requested_at', 'claimed_at',
        'consent_at', 'consent_source', 'email_blocked_at', 'optout_token',
        'email_verified_at', 'phone_verified_at', 'phone_code', 'phone_code_sent_at', 'terms_accepted_at', 'terms_version',
        'stripe_account_id', 'stripe_charges_enabled', 'stripe_payouts_enabled', 'stripe_requirements', 'stripe_checked_at',
        'insurance_until', 'insurance_note', 'verification_requested_at', 'verified_at', 'suspended_at', 'suspended_until', 'blocked_at',
        'rating_avg', 'rating_count', 'jobs_done', 'response_minutes', 'notes',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'languages' => 'array',
        'rates' => 'array',
        'stripe_requirements' => 'array',
        'invoice_promise' => 'boolean',
        'protect_opt_in' => 'boolean',
        'stripe_charges_enabled' => 'boolean',
        'stripe_payouts_enabled' => 'boolean',
        'rating_avg' => 'float',
        'source_fetched_at' => 'datetime',
        'vat_checked_at' => 'datetime',
        'claim_requested_at' => 'datetime',
        'claimed_at' => 'datetime',
        'consent_at' => 'datetime',
        'email_blocked_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'phone_code_sent_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'stripe_checked_at' => 'datetime',
        'insurance_until' => 'date:Y-m-d',
        'verification_requested_at' => 'datetime',
        'verified_at' => 'datetime',
        'suspended_at' => 'datetime',
        'suspended_until' => 'date:Y-m-d',
        'blocked_at' => 'datetime',
    ];

    /** Geheimen die nooit in een Inertia-antwoord horen. */
    protected $hidden = ['claim_token', 'claim_code', 'phone_code', 'optout_token', 'photo_data', 'notes'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withoutGlobalScope('company');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Demo-bedrijf (demo-administratie van App\Services\Fachora\DemoBuilder): geen mail en geen eigenaarsberichten. */
    public function isDemo(): bool
    {
        return (bool) $this->company?->is_demo;
    }

    public function checks(): HasMany
    {
        return $this->hasMany(ProviderCheck::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(ServiceJobOffer::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(ServiceJob::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ServiceReview::class)->where('published', true)->latest();
    }

    public function files(): HasMany
    {
        return $this->hasMany(FachoraFile::class, 'owner_id')->where('owner_type', 'provider');
    }

    /** @var list<string>|null */
    private ?array $categoriesCache = null;

    /** Categorieën (sleutels uit config/fachora.php). @return list<string> */
    public function categories(): array
    {
        return $this->categoriesCache ??= \Illuminate\Support\Facades\DB::table('provider_category')
            ->where('provider_id', $this->id)->orderBy('category')->pluck('category')->all();
    }

    /** @param  list<string>  $categories */
    public function syncCategories(array $categories): void
    {
        $known = array_keys((array) config('fachora.categories', []));
        $categories = array_values(array_unique(array_intersect($categories, $known)));
        \Illuminate\Support\Facades\DB::table('provider_category')->where('provider_id', $this->id)->delete();
        if ($categories !== []) {
            \Illuminate\Support\Facades\DB::table('provider_category')->insert(array_map(fn ($c) => ['provider_id' => $this->id, 'category' => $c], $categories));
        }
        $this->categoriesCache = null;
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('providers.status', 'verified');
    }

    public function scopeInCategory(Builder $query, string $category): Builder
    {
        return $query->whereExists(fn ($q) => $q->selectRaw('1')->from('provider_category')
            ->whereColumn('provider_category.provider_id', 'providers.id')->where('provider_category.category', $category));
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    /** Geclaimd of verder: dan is er een eigenaar die zelf heeft gevraagd om berichten van Fachora. */
    public function isClaimed(): bool
    {
        return in_array($this->status, ['claimed', 'pending', 'verified', 'suspended'], true) && $this->claimed_at !== null;
    }

    /** Mag een opdracht worden aangeboden: Verified, met een werkend contactkanaal. */
    public function canReceiveJobs(): bool
    {
        return $this->isVerified() && filled($this->email) && $this->email_blocked_at === null;
    }

    /** Heeft Fachora Protect via Stripe Connect op dit bedrijf klaarstaan? */
    public function protectReady(): bool
    {
        return $this->protect_opt_in && filled($this->stripe_account_id) && $this->stripe_payouts_enabled;
    }

    /** Spreekt het bedrijf deze taal (Pools altijd)? */
    public function speaks(string $language): bool
    {
        return $language === 'pl' || isset(($this->languages ?? [])[$language]);
    }

    /** Fee die voor dit bedrijf geldt: introductietarief in de eerste maanden na verificatie. */
    public function feePercent(): float
    {
        $intro = $this->verified_at && $this->verified_at->gt(now()->subDays((int) config('fachora.intro_days', 90)));

        return (float) config($intro ? 'fachora.intro_fee_percent' : 'fachora.fee_percent');
    }

    public function publicUrl(): string
    {
        return route('fachora.provider.show', $this->slug);
    }

    public function claimUrl(): string
    {
        return route('fachora.claim.confirm.show', $this->claim_token);
    }

    public function optoutUrl(): string
    {
        return route('fachora.optout', $this->optout_token);
    }

    /** Unieke slug uit naam en plaats. */
    public static function makeSlug(string $name, ?string $city, ?int $ignoreId = null): string
    {
        $base = Str::slug(Str::ascii(trim($name . ' ' . ($city ?? '')))) ?: 'firma';
        $slug = $base;
        for ($i = 2; static::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }

    /** Korte, leesbare claimcode voor op een brief (zonder 0/O/1/I). */
    public static function makeClaimCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::query()->where('claim_code', $code)->exists());

        return $code;
    }

    /** Wat de openbare profielpagina en de klant mogen zien. @return array<string, mixed> */
    public function publicSummary(): array
    {
        $languages = (array) config('fachora.languages', []);
        $levels = (array) config('fachora.levels', []);
        $locale = app()->getLocale() === 'en' ? 'en' : 'pl';

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->display_name,
            'city' => $this->city,
            'status' => $this->status,
            'verified' => $this->isVerified(),
            'verified_since' => $this->verified_at?->translatedFormat('F Y'),
            'categories' => $this->categories(),
            'languages' => collect($this->languages ?? [])->map(fn ($level, $code) => [
                'code' => $code,
                'name' => $languages[$code][$locale] ?? strtoupper($code),
                'flag' => $languages[$code]['flag'] ?? '',
                'level' => $levels[$level][$locale] ?? $level,
            ])->values()->all(),
            'radius_km' => $this->radius_km,
            'rates' => $this->rates,
            'warranty_months' => $this->warranty_months,
            'invoice' => $this->invoice_promise,
            'protect' => $this->protectReady(),
            'insured' => $this->insurance_until !== null && $this->insurance_until->isFuture(),
            'rating' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'jobs_done' => $this->jobs_done,
            'description' => $this->description,
            'photo' => $this->photo_data,
            'url' => $this->publicUrl(),
        ];
    }
}
