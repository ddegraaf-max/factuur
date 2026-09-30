<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eén gekochte bundel sms-tegoed; pas na betaling telt het tegoed mee. */
class SmsPurchase extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'credits', 'price_excl', 'vat_rate', 'price_incl', 'status', 'stripe_session_id', 'paid_at',
    ];

    protected $casts = [
        'credits' => 'integer',
        'price_excl' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'price_incl' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
