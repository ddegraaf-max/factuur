<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Eén incassobatch: een pain.008-bestand met één of meer facturen die bij de bank worden ingediend. */
class DirectDebitBatch extends Model
{
    protected $fillable = ['company_id', 'reference', 'collection_date', 'count', 'total', 'lines', 'created_by', 'downloaded_at'];

    protected $casts = [
        'collection_date' => 'date',
        'lines' => 'array',
        'downloaded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('direct_debit_batches.company_id', auth()->user()->company_id);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'direct_debit_batch_id');
    }
}
