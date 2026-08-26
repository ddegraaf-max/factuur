<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use HasFactory;
    use \App\Models\Concerns\HasBrandProfile;

    public const STATUSES = [
        'draft' => 'Concept',
        'sent' => 'Verstuurd',
        'accepted' => 'Geaccepteerd',
        'rejected' => 'Afgewezen',
        'expired' => 'Verlopen',
    ];

    protected $fillable = [
        'company_id', 'customer_id', 'brand_profile_id', 'number', 'portal_token', 'reference', 'status',
        'quote_date', 'valid_until', 'language',
        'customer_name', 'customer_address_line', 'customer_postal_code',
        'customer_city', 'customer_country', 'customer_vat_number',
        'customer_kvk_number', 'customer_email',
        'subtotal', 'vat_total', 'total', 'vat_breakdown',
        'intro', 'notes', 'footer',
        'sent_at', 'accepted_at', 'rejected_at', 'converted_invoice_id',
        'signed_name', 'signature_data', 'signed_at', 'signed_ip', 'signed_email', 'decline_reason',
        'accept_mail_sent_at', 'accept_mail_sent_to',
    ];

    protected $casts = [
        'quote_date' => 'date',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'signed_at' => 'datetime',
        'accept_mail_sent_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'vat_total' => 'decimal:2',
        'total' => 'decimal:2',
        'vat_breakdown' => 'array',
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

        return (int) ceil(now()->floatDiffInDays($this->valid_until));
    }

    public function scopeForStatus(Builder $query, ?string $status): Builder
    {
        if (! $status || $status === 'all') return $query;

        return $query->where('status', $status);
    }
}
