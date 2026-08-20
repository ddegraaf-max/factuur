<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén termijn van een termijnplan op een offerte: een percentage van de
 * offertesom met een omschrijving. Zodra de termijn is gefactureerd wijst
 * invoice_id naar de (concept)factuur en ligt de termijn vast.
 */
class QuoteInstallment extends Model
{
    protected $fillable = [
        'company_id', 'quote_id', 'sort_order',
        'description', 'percentage', 'amount', 'invoice_id',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('quote_installments.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (QuoteInstallment $installment) {
            if (! $installment->company_id && auth()->check()) {
                $installment->company_id = auth()->user()->company_id;
            }
        });
    }

    public function quote(): BelongsTo { return $this->belongsTo(Quote::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class)->withoutGlobalScope('company'); }
}
