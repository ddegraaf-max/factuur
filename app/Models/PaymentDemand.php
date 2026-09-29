<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Online aanmaning: de laatste aanmaning vóór de deurwaarder. De klant krijgt
 * een geheime link naar een pagina met het bedrag van vandaag (hoofdsom,
 * wettelijke rente en na de termijn de incassokosten) en reageert daar:
 * betaald, ik betaal op, of ik ben het er niet mee eens.
 *
 * Twee soorten:
 *  - bij een factuur in een administratie (invoice_id gevuld);
 *  - los, gemaakt op de website zonder account (invoice_id leeg). Schuldeiser,
 *    klant en factuur staan dan op de aanmaning zelf, en ze is pas actief
 *    nadat de schuldeiser zijn e-mailadres heeft bevestigd.
 *
 * @property-read Invoice|null $invoice  de factuur, of bij een losse aanmaning een factuur die alleen in het geheugen bestaat
 */
class PaymentDemand extends Model
{
    public const RESPONSES = ['paid', 'promise', 'dispute'];

    /** Zo lang is de link in de bevestigingsmail geldig. */
    public const CONFIRM_DAYS = 7;

    protected $fillable = [
        'company_id', 'invoice_id', 'token', 'status', 'debtor_type', 'sent_to', 'principal', 'with_interest',
        'auto_transfer', 'costs', 'costs_vat', 'term_days', 'deadline', 'sent_at', 'first_opened_at', 'response', 'response_date',
        'response_note', 'responded_at', 'expiry_notified_at', 'closed_at',
        'creditor_key', 'confirmed_at', 'confirm_ip', 'creator_ip', 'confirm_mails',
        'creditor_name', 'creditor_email', 'creditor_kvk', 'creditor_iban', 'creditor_address', 'creditor_phone', 'creditor_no_vat',
        'debtor_name', 'debtor_kvk', 'debtor_address', 'debtor_facts',
        'invoice_number', 'invoice_date', 'due_date', 'amount',
    ];

    protected $hidden = ['creditor_key'];

    protected $casts = [
        'with_interest' => 'boolean',
        'auto_transfer' => 'boolean',
        'creditor_no_vat' => 'boolean',
        'principal' => 'decimal:2',
        'costs' => 'decimal:2',
        'costs_vat' => 'decimal:2',
        'amount' => 'decimal:2',
        'term_days' => 'integer',
        'confirm_mails' => 'integer',
        'deadline' => 'date:Y-m-d',
        'response_date' => 'date:Y-m-d',
        'invoice_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'first_opened_at' => 'datetime',
        'responded_at' => 'datetime',
        'expiry_notified_at' => 'datetime',
        'closed_at' => 'datetime',
        'debtor_facts' => 'array',
    ];

    /** De factuur van een losse aanmaning: bestaat alleen in het geheugen. */
    protected ?Invoice $virtualInvoice = null;

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('payment_demands.company_id', auth()->user()->company_id);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withoutGlobalScope('company');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentDemandEvent::class)->orderBy('created_at')->orderBy('id');
    }

    /** Kopie van de factuur die de schuldeiser meestuurde (losse aanmaning). */
    public function file(): HasOne
    {
        return $this->hasOne(PaymentDemandFile::class);
    }

    /** Naam en omvang van de kopie, zonder het bestand zelf te laden. */
    public function fileInfo(): ?PaymentDemandFile
    {
        if ($this->relationLoaded('file')) {
            return $this->getRelation('file');
        }

        return $this->exists ? $this->file()->first(['id', 'payment_demand_id', 'filename', 'mime_type', 'size_bytes']) : null;
    }

    /**
     * $demand->invoice: de echte factuur, of bij een losse aanmaning een
     * factuur (met bedrijf) die uit de gegevens op de aanmaning is opgebouwd en
     * nooit wordt opgeslagen. Zo rekenen brief, pagina en mail voor beide
     * soorten op dezelfde manier.
     */
    public function getInvoiceAttribute(): ?Invoice
    {
        if (! $this->isStandalone() || $this->relationLoaded('invoice') && $this->getRelation('invoice')) {
            return $this->getRelationValue('invoice');
        }

        return $this->virtualInvoice ??= $this->buildInvoice();
    }

    /** Los gemaakt op de website, zonder administratie. */
    public function isStandalone(): bool
    {
        return ! $this->invoice_id;
    }

    /** De geheime link uit de mail: de pagina met het bedrag van vandaag. */
    public function url(): string
    {
        return route('demand.show', $this->token);
    }

    /** De link van de schuldeiser: dezelfde pagina, met zijn sleutel. Niet doorsturen. */
    public function creditorUrl(): ?string
    {
        return $this->creditor_key ? route('demand.show', ['token' => $this->token, 'k' => $this->creditor_key]) : null;
    }

    public function confirmUrl(): ?string
    {
        return $this->creditor_key ? route('demand.confirm', ['token' => $this->token, 'k' => $this->creditor_key]) : null;
    }

    /** Is dit de schuldeiser? Alleen met de sleutel uit zijn eigen mail. */
    public function isCreditorKey(mixed $key): bool
    {
        return is_string($key) && filled($this->creditor_key) && hash_equals((string) $this->creditor_key, $key);
    }

    /** Wacht nog op de bevestiging van het e-mailadres; voor de buitenwereld bestaat ze dan niet. */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function confirmExpired(): bool
    {
        return $this->isPending() && $this->created_at && $this->created_at->copy()->addDays(self::CONFIRM_DAYS)->isPast();
    }

    public function isBusiness(): bool
    {
        return $this->debtor_type === 'business';
    }

    /** Loopt de aanmaning nog: verstuurd, niet ingetrokken, betaald of overgedragen. */
    public function isActive(): bool
    {
        return $this->status === 'sent';
    }

    /** De klant heeft gereageerd; een tweede reactie komt er niet. */
    public function isAnswered(): bool
    {
        return filled($this->response);
    }

    /** De termijn is voorbij zodra de laatste dag om is. */
    public function isExpired(): bool
    {
        return $this->deadline->copy()->endOfDay()->isPast();
    }

    /** Klaar voor de deurwaarder: termijn voorbij en de aanmaning loopt nog. */
    public function isDue(): bool
    {
        return $this->isActive() && $this->isExpired();
    }

    /** Bedrijf en factuur van een losse aanmaning, alleen in het geheugen. */
    protected function buildInvoice(): Invoice
    {
        $address = fn (?string $text) => app(\App\Services\FreeInvoiceImport::class)->address((string) $text);

        $company = (new Company())->forceFill([
            'name' => $this->creditor_name,
            'email' => $this->creditor_email,
            'phone' => $this->creditor_phone,
            'kvk_number' => $this->creditor_kvk,
            'iban' => $this->creditor_iban,
            'kor' => (bool) $this->creditor_no_vat,
            'brand_color' => (string) brand('color'),
        ] + $address($this->creditor_address));

        $customer = $address($this->debtor_address);
        $paid = $this->status === 'paid';
        $invoice = (new Invoice())->forceFill([
            'number' => $this->invoice_number,
            'status' => $paid ? 'paid' : 'overdue',
            'is_credit' => false,
            'language' => 'nl',
            'invoice_date' => $this->invoice_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'total' => (float) $this->amount,
            'paid_total' => $paid ? (float) $this->amount : 0,
            'customer_name' => $this->debtor_name,
            'customer_email' => $this->sent_to ?: null,
            'customer_kvk_number' => $this->debtor_kvk,
            'customer_address_line' => $customer['address_line'] ?? null,
            'customer_postal_code' => $customer['postal_code'] ?? null,
            'customer_city' => $customer['city'] ?? null,
        ]);

        return $invoice
            ->setRelation('company', $company)
            ->setRelation('brandProfile', null)
            ->setRelation('customer', null)
            ->setRelation('lines', collect())
            ->setRelation('payments', collect())
            ->setRelation('reminderLogs', collect())
            ->setRelation('attachments', collect());
    }

    /** Na een wijziging van de stand (betaald) de opgebouwde factuur opnieuw maken. */
    public function forgetInvoice(): static
    {
        $this->virtualInvoice = null;

        return $this;
    }
}
