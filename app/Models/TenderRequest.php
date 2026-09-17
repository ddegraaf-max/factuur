<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén prijsaanvraag aan één bedrijf binnen een uitvraagronde. De tokenlink in
 * de mail leidt naar het reactieformulier; daarop landen prijs, beschikbaarheid
 * en een eventuele eigen offerte-PDF.
 */
class TenderRequest extends Model
{
    public const STATUSES = [
        'sent' => 'Aangeschreven', 'responded' => 'Prijs ontvangen', 'declined' => 'Afgezegd',
        'awarded' => 'Gegund', 'rejected' => 'Niet gegund',
    ];

    protected $fillable = [
        'tender_round_id', 'subcontractor_id', 'token', 'status', 'sent_at', 'reminded_at', 'opened_at',
        'responded_at', 'price', 'available_week', 'valid_until', 'remarks', 'decline_reason',
        'attachment_name', 'attachment_path',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'reminded_at' => 'datetime',
        'opened_at' => 'datetime',
        'responded_at' => 'datetime',
        'valid_until' => 'date',
        'price' => 'decimal:2',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(TenderRound::class, 'tender_round_id')->withoutGlobalScope('company');
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class)->withoutGlobalScope('company');
    }

    /** De geheime link uit de mail: hier geeft het bedrijf prijs en beschikbaarheid door. */
    public function responseUrl(): string
    {
        return route('tender.respond.show', $this->token);
    }

    public function hasPrice(): bool
    {
        return $this->price !== null && in_array($this->status, ['responded', 'awarded', 'rejected'], true);
    }
}
