<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Een opdracht (zlecenie) van een klant: van aanvraag tot uitvoering,
 * akkoord, uitbetaling en beoordeling. De klant volgt alles via de geheime
 * link met het token; het bedrijf via zijn aanbieding (ServiceJobOffer).
 */
class ServiceJob extends Model
{
    public const STATUSES = [
        'pending' => 'Wacht op bevestiging', 'selecting' => 'Klant kiest bedrijven', 'open' => 'Aangeboden', 'quoted' => 'Prijs ontvangen', 'no_match' => 'Geen bedrijf gevonden',
        'agreed' => 'Bedrijf gekozen', 'scheduled' => 'Ingepland', 'in_progress' => 'In uitvoering', 'done' => 'Klaar',
        'accepted' => 'Afgerond', 'disputed' => 'Geschil', 'cancelled' => 'Geannuleerd', 'expired' => 'Verlopen zonder reactie',
    ];

    /** Vanaf hier is er een bedrijf aan de opdracht verbonden. */
    public const WITH_PROVIDER = ['agreed', 'scheduled', 'in_progress', 'done', 'accepted', 'disputed'];

    /** Domein van de klant-e-mails in de demo-omgeving (App\Services\Fachora\DemoBuilder): daar gaat nooit mail of sms heen. */
    public const DEMO_DOMAIN = 'demo.fachora.pl';

    public const CLOSED = ['accepted', 'cancelled', 'expired'];

    protected $fillable = [
        'reference', 'token', 'status', 'category', 'title', 'description', 'description_pl', 'ai', 'urgency', 'wanted_date', 'time_window',
        'language', 'customer_name', 'customer_email', 'customer_phone', 'customer_locale', 'postal_code', 'city', 'address_line', 'lat', 'lng',
        'creator_ip', 'confirmed_at', 'confirm_mails', 'estimate_low', 'estimate_high',
        'offered_at', 'offers_deadline_at', 'waves', 'chosen_offer_id', 'provider_id', 'agreed_amount', 'agreed_type', 'agreed_scope', 'agreed_at',
        'scheduled_at', 'scheduled_window', 'warranty_months',
        'protect', 'stripe_session_id', 'stripe_payment_intent', 'stripe_charge_id', 'stripe_transfer_id', 'stripe_refund_id',
        'paid_at', 'payout_at', 'refunded_at', 'fee_percent', 'fee_amount', 'payout_amount',
        'started_at', 'done_at', 'done_note', 'accepted_at', 'accepted_by', 'dispute_opened_at', 'dispute_reason', 'dispute_reply', 'dispute_replied_at',
        'dispute_resolution', 'dispute_note', 'resolved_at', 'cancelled_at', 'cancelled_by', 'cancel_reason', 'invoice_id', 'reviewed_at', 'owner_notified_at',
        'selected_providers', 'request_fee', 'request_session_id', 'request_paid_at',
        'phone_code', 'phone_code_sent_at', 'phone_code_attempts', 'phone_verified_at', 'provider_search_at',
    ];

    protected $casts = [
        'ai' => 'array',
        'wanted_date' => 'date:Y-m-d',
        'lat' => 'float',
        'lng' => 'float',
        'confirmed_at' => 'datetime',
        'estimate_low' => 'float',
        'estimate_high' => 'float',
        'offered_at' => 'datetime',
        'offers_deadline_at' => 'datetime',
        'agreed_amount' => 'float',
        'agreed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'protect' => 'boolean',
        'paid_at' => 'datetime',
        'payout_at' => 'datetime',
        'refunded_at' => 'datetime',
        'fee_percent' => 'float',
        'fee_amount' => 'float',
        'payout_amount' => 'float',
        'started_at' => 'datetime',
        'done_at' => 'datetime',
        'accepted_at' => 'datetime',
        'dispute_opened_at' => 'datetime',
        'dispute_replied_at' => 'datetime',
        'resolved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'owner_notified_at' => 'datetime',
        'selected_providers' => 'array',
        'request_fee' => 'float',
        'request_paid_at' => 'datetime',
        'phone_code_sent_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'provider_search_at' => 'datetime',
    ];

    protected $hidden = ['token', 'creator_ip', 'stripe_session_id', 'stripe_payment_intent', 'stripe_charge_id', 'stripe_transfer_id', 'stripe_refund_id', 'request_session_id', 'phone_code'];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(ServiceJobOffer::class)->orderBy('id');
    }

    public function chosenOffer(): BelongsTo
    {
        return $this->belongsTo(ServiceJobOffer::class, 'chosen_offer_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ServiceJobEvent::class)->orderBy('id');
    }

    public function extras(): HasMany
    {
        return $this->hasMany(ServiceJobExtra::class)->orderBy('id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(ServiceReview::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(FachoraFile::class, 'owner_id')->where('owner_type', 'job')->orderBy('id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withoutGlobalScope('company');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, self::CLOSED, true);
    }

    public function hasProvider(): bool
    {
        return in_array($this->status, self::WITH_PROVIDER, true) && $this->provider_id !== null;
    }

    /** Wacht de opdracht op de keuze van bedrijven (en de betaling van de aanvraag) door de klant? */
    public function isSelecting(): bool
    {
        return $this->status === 'selecting';
    }

    /** Kan de klant nog een bedrijf kiezen? */
    public function canChoose(): bool
    {
        return in_array($this->status, ['open', 'quoted'], true);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null && $this->refunded_at === null;
    }

    /** Totaal incl. goedgekeurd meerwerk. */
    public function totalAmount(): float
    {
        return (float) $this->agreed_amount + (float) $this->extras()->where('status', 'approved')->sum('amount');
    }

    public function url(): string
    {
        return route('fachora.job.show', $this->token);
    }

    public function confirmUrl(): string
    {
        return route('fachora.job.confirm.show', $this->token);
    }

    /**
     * Volgend kenmerk: FA-10001, FA-10002 … Uit het hoogste bestaande kenmerk,
     * niet uit het id: een Postgres-sequence springt na een teruggedraaide
     * transactie, en dan zou het kenmerk gaten vertonen.
     */
    /** Demo-zaak: de klant bestaat niet, dus geen mail, sms of eigenaarsbericht (zie Notifier). */
    public function isDemo(): bool
    {
        return str_ends_with(mb_strtolower((string) $this->customer_email), '@' . self::DEMO_DOMAIN);
    }

    public static function nextReference(): string
    {
        $last = (int) static::query()->selectRaw('MAX(CAST(SUBSTR(reference, 4) AS INTEGER)) as n')->value('n');

        return 'FA-' . max(10001, $last + 1);
    }

    public function categoryName(?string $locale = null): string
    {
        $locale = $locale ?: (app()->getLocale() === 'en' ? 'en' : 'pl');

        return (string) config("fachora.categories.{$this->category}.{$locale}", $this->category);
    }

    public function statusLabel(): string
    {
        return __(self::STATUSES[$this->status] ?? $this->status);
    }
}
