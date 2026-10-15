<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eén verificatiestap van een bedrijf (register, btw, e-mail, telefoon, voorwaarden, bank, verzekering). */
class ProviderCheck extends Model
{
    protected $fillable = ['provider_id', 'kind', 'status', 'evidence', 'note', 'checked_at', 'checked_by'];

    protected $casts = ['evidence' => 'array', 'checked_at' => 'datetime'];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function passed(): bool
    {
        return in_array($this->status, ['passed', 'manual'], true);
    }
}
