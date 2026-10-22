<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén aanbieding van een opdracht aan één bedrijf, met de geheime link
 * waarop het bedrijf prijs en beschikbaarheid doorgeeft (zoals de
 * prijsaanvraag aan een onderaannemer).
 */
class ServiceJobOffer extends Model
{
    public const STATUSES = [
        'sent' => 'Aangeboden', 'responded' => 'Prijs ontvangen', 'declined' => 'Afgezegd',
        'chosen' => 'Gekozen', 'rejected' => 'Niet gekozen', 'expired' => 'Verlopen',
    ];

    protected $fillable = [
        'service_job_id', 'provider_id', 'token', 'status', 'wave', 'sent_at', 'reminded_at', 'opened_at', 'responded_at',
        'price', 'price_type', 'callout', 'hourly', 'available_at', 'available_note', 'remarks', 'decline_reason',
        'lead_fee', 'lead_free', 'lead_session_id', 'unlocked_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'reminded_at' => 'datetime',
        'opened_at' => 'datetime',
        'responded_at' => 'datetime',
        'available_at' => 'datetime',
        'price' => 'float',
        'callout' => 'float',
        'hourly' => 'float',
        'lead_fee' => 'float',
        'lead_free' => 'boolean',
        'unlocked_at' => 'datetime',
    ];

    protected $hidden = ['token', 'lead_session_id'];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function url(): string
    {
        return route('fachora.offer.show', $this->token);
    }

    public function hasPrice(): bool
    {
        return $this->price !== null && in_array($this->status, ['responded', 'chosen', 'rejected'], true);
    }

    /** Kan het bedrijf nog reageren (prijs geven of afzeggen)? */
    public function isOpen(): bool
    {
        return in_array($this->status, ['sent', 'responded'], true) && $this->job?->canChoose();
    }

    public function isUnlocked(): bool
    {
        return $this->unlocked_at !== null;
    }

    public function statusLabel(): string
    {
        return __(self::STATUSES[$this->status] ?? $this->status);
    }
}
