<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tijdlijn van een opdracht: wie deed wat en wanneer. */
class ServiceJobEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['service_job_id', 'kind', 'actor', 'description', 'data', 'created_at'];

    protected $casts = ['data' => 'array', 'created_at' => 'datetime'];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }
}
