<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Aanvraag voor toegang tot de demo (1.86.0). De demo staat niet meer open
 * voor iedereen: een bezoeker laat zijn e-mailadres achter, de eigenaar keurt
 * goed (of wijst af), en pas dan krijgt de aanvrager een persoonlijke link.
 * Per merk: een aanvraag op fachora.pl hoort bij Fachora.
 */
class DemoRequest extends Model
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const DENIED = 'denied';

    /** Hoe lang een goedgekeurde link werkt; opnieuw goedkeuren verlengt. */
    public const VALID_DAYS = 14;

    protected $fillable = [
        'brand', 'email', 'name', 'company', 'message', 'locale', 'token', 'review_token',
        'status', 'approved_at', 'denied_at', 'expires_at', 'starts', 'last_started_at', 'ip',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'denied_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_started_at' => 'datetime',
        'starts' => 'integer',
    ];

    public static function newToken(): string
    {
        return Str::random(48);
    }

    public function scopeForBrand(Builder $query, string $brand): Builder
    {
        return $query->where('brand', $brand);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::APPROVED;
    }

    /** Mag deze link nu een demo starten? Goedgekeurd en nog niet verlopen. */
    public function isUsable(): bool
    {
        return $this->isApproved() && $this->expires_at !== null && $this->expires_at->isFuture();
    }

    public function displayName(): string
    {
        return trim((string) $this->name) !== '' ? (string) $this->name : (string) $this->email;
    }
}
