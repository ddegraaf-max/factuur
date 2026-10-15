<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Meerwerk: door het bedrijf voorgesteld, door de klant goedgekeurd of afgewezen (Fachora Standard 04). */
class ServiceJobExtra extends Model
{
    protected $fillable = ['service_job_id', 'description', 'amount', 'status', 'decided_at'];

    protected $casts = ['amount' => 'float', 'decided_at' => 'datetime'];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }
}
