<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Status van één btw-aangiftetijdvak: aangegeven, betaald, handmatige
 * rubrieken en (eventueel) een afwijkend betalingskenmerk.
 */
class VatFiling extends Model
{
    protected $fillable = [
        'company_id', 'year', 'period_type', 'period',
        'filed_at', 'paid_at', 'payment_reference', 'manual', 'notes',
        'reminded_at', 'reminded_final_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'period' => 'integer',
        'filed_at' => 'datetime',
        'paid_at' => 'datetime',
        'manual' => 'array',
        'reminded_at' => 'datetime',
        'reminded_final_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('vat_filings.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (VatFiling $filing) {
            if (! $filing->company_id && auth()->check()) {
                $filing->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
