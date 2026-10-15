<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Beoordeling na een afgeronde, betaalde opdracht; het bedrijf mag antwoorden, nooit verwijderen. */
class ServiceReview extends Model
{
    protected $fillable = ['service_job_id', 'provider_id', 'rating', 'text', 'reply', 'replied_at', 'published'];

    protected $casts = ['rating' => 'integer', 'replied_at' => 'datetime', 'published' => 'boolean'];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
