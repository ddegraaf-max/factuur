<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int|null $installments_count            withCount('installments') in de lijst (QuoteController::index)
 * @property-read int|null $installments_done_count       withCount: termijnen met een factuur
 * @property-read float|null $invoiced_percent            withSum: gefactureerd percentage van het termijnplan
 */
class Quote extends Model
{
    use HasFactory;
    use \App\Models\Concerns\HasBrandProfile;
    use \App\Models\Concerns\HasVatTreatment;

    public const STATUSES = [
        'draft' => 'Concept',
        'sent' => 'Verstuurd',
        'accepted' => 'Geaccepteerd',
        'rejected' => 'Afgewezen',
        'expired' => 'Verlopen',
        // Door de afzender ingetrokken, bijv. vervangen door een nieuwe offerte.
        'withdrawn' => 'Vervallen',
    ];

    protected $fillable = [
        'company_id', 'project_id', 'customer_id', 'brand_profile_id', 'number', 'portal_token', 'reference', 'status',
        'quote_date', 'valid_until', 'language',
        'customer_name', 'customer_address_line', 'customer_postal_code',
        'customer_city', 'customer_country', 'customer_vat_number',
        'customer_kvk_number', 'customer_email',
        'subtotal', 'vat_total', 'total', 'vat_breakdown', 'vat_reversed', 'vat_exempt',
        'intro', 'notes', 'footer',
        'sent_at', 'accepted_at', 'rejected_at', 'converted_invoice_id',
        'portal_viewed_at', 'portal_views', 'reminders_enabled', 'reminder_log',
        'signed_name', 'signature_data', 'signed_at', 'signed_ip', 'signed_email', 'decline_reason',
        // 1.107: tweede ondertekenaar (twee handtekeningen nodig).
        'signers_required', 'co_signer_name', 'co_signer_email', 'co_signer_token', 'co_signer_invited_at',
        'co_signed_name', 'co_signature_data', 'co_signed_at', 'co_signed_ip', 'co_signed_email',
        'accept_mail_sent_at', 'accept_mail_sent_to',
        'withdrawn_at', 'withdrawal_note', 'replaced_by_quote_id', 'withdrawal_mail_sent_at', 'withdrawal_mail_sent_to',
    ];

    protected $casts = [
        // Als jjjj-mm-dd naar het scherm; zie de toelichting bij Invoice.
        'quote_date' => 'date:Y-m-d',
        'valid_until' => 'date:Y-m-d',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'signed_at' => 'datetime',
        'co_signed_at' => 'datetime',
        'co_signer_invited_at' => 'datetime',
        'accept_mail_sent_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'withdrawal_mail_sent_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'vat_total' => 'decimal:2',
        'total' => 'decimal:2',
        'portal_viewed_at' => 'datetime',
        'portal_views' => 'integer',
        'reminders_enabled' => 'boolean',
        'reminder_log' => 'array',
        'vat_breakdown' => 'array',
        'vat_reversed' => 'boolean',
        'vat_exempt' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('quotes.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (Quote $quote) {
            if (! $quote->company_id && auth()->check()) {
                $quote->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class)->withoutGlobalScope('company'); }
    public function lines(): HasMany { return $this->hasMany(QuoteLine::class)->orderBy('sort_order'); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class, 'converted_invoice_id')->withoutGlobalScope('company'); }
    public function installments(): HasMany { return $this->hasMany(QuoteInstallment::class)->orderBy('sort_order'); }
    /** De offerte die deze (vervallen) offerte vervangt. */
    public function replacedBy(): BelongsTo { return $this->belongsTo(Quote::class, 'replaced_by_quote_id')->withoutGlobalScope('company'); }
    public function attachments(): \Illuminate\Database\Eloquent\Relations\MorphMany { return $this->morphMany(Attachment::class, 'attachable'); }

    /** Geheime link voor het klantenportaal (bekijken en ondertekenen). */
    public function ensurePortalToken(): string
    {
        if (! $this->portal_token) {
            $this->portal_token = bin2hex(random_bytes(32));
            $this->saveQuietly();
        }

        return $this->portal_token;
    }

    public function portalUrl(): ?string
    {
        return $this->portal_token
            ? route('portal.quote', $this->portal_token)
            : null;
    }

    /** 1.107: moet deze offerte door twee personen worden ondertekend (bijv. twee bestuurders)? */
    public function needsCoSigner(): bool
    {
        return (int) $this->signers_required >= 2 && filled($this->co_signer_email);
    }

    /** Eigen geheime portaallink voor de tweede ondertekenaar (los van die van de klant). */
    public function ensureCoSignerToken(): string
    {
        if (! $this->co_signer_token) {
            $this->co_signer_token = bin2hex(random_bytes(32));
            $this->saveQuietly();
        }

        return $this->co_signer_token;
    }

    public function coSignerPortalUrl(): ?string
    {
        return $this->needsCoSigner() && $this->co_signer_token ? route('portal.quote', $this->co_signer_token) : null;
    }

    /**
     * De ondertekenaars op volgorde: de klant en (bij twee) de tweede ondertekenaar, elk met wat er al getekend is.
     *
     * @return list<array{role: string, name: ?string, email: ?string, signed_name: ?string, signed_at: ?\Carbon\CarbonInterface, signed_ip: ?string, signature_data: ?string}>
     */
    public function signers(): array
    {
        $list = [[
            'role' => 'customer', 'name' => $this->customer_name, 'email' => $this->customer_email,
            'signed_name' => $this->signed_name, 'signed_at' => $this->signed_at, 'signed_ip' => $this->signed_ip, 'signature_data' => $this->signature_data,
        ]];
        if ($this->needsCoSigner()) {
            $list[] = [
                'role' => 'co', 'name' => $this->co_signer_name, 'email' => $this->co_signer_email,
                'signed_name' => $this->co_signed_name, 'signed_at' => $this->co_signed_at, 'signed_ip' => $this->co_signed_ip, 'signature_data' => $this->co_signature_data,
            ];
        }

        return $list;
    }

    /** Handtekeningen die er al staan (0, 1 of 2). */
    public function signaturesDone(): int
    {
        return count(array_filter($this->signers(), fn (array $s) => $s['signed_at'] !== null));
    }

    public function isFullySigned(): bool
    {
        return $this->signaturesDone() >= count($this->signers());
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Verlopen = verstuurd, niet beantwoord en de geldigheidsdatum is voorbij. */
    public function getIsExpiredAttribute(): bool
    {
        return $this->status === 'sent'
            && $this->valid_until
            && $this->valid_until->isPast();
    }

    public function getDaysLeftAttribute(): int
    {
        if (! $this->valid_until || $this->valid_until->isPast()) {
            return 0;
        }

        return (int) ceil((float) now()->diffInDays($this->valid_until, true));
    }

    public function scopeForStatus(Builder $query, ?string $status): Builder
    {
        if (! $status || $status === 'all') return $query;

        return $query->where('status', $status);
    }
}
