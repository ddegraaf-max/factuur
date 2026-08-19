<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén online betaalpoging (iDEAL via Mollie) vanuit het klantenportaal.
 * Bewust zonder company-scope: dit model leeft in het portaal en de webhook,
 * waar geen ingelogde gebruiker is. Query's filteren expliciet op factuur.
 */
class OnlinePayment extends Model
{
    protected $fillable = [
        'company_id', 'invoice_id', 'payment_id',
        'mollie_id', 'checkout_url', 'amount', 'status', 'method', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class)->withoutGlobalScope('company'); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class)->withoutGlobalScope('company'); }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'pending'], true);
    }
}
