<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    protected $fillable = [
        'company_id', 'booking_date', 'amount', 'currency',
        'counterparty_name', 'counterparty_iban', 'description',
        'status', 'matched_invoice_id', 'matched_purchase_id', 'payment_id',
        'source', 'import_hash',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('bank_transactions.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (BankTransaction $tx) {
            if (! $tx->company_id && auth()->check()) {
                $tx->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function matchedInvoice(): BelongsTo { return $this->belongsTo(Invoice::class, 'matched_invoice_id')->withoutGlobalScope('company'); }
    public function matchedPurchase(): BelongsTo { return $this->belongsTo(PurchaseInvoice::class, 'matched_purchase_id')->withoutGlobalScope('company'); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class)->withoutGlobalScope('company'); }
}
