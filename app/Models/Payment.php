<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'invoice_id', 'amount', 'paid_on',
        'method', 'reference', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_on' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('payments.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (Payment $payment) {
            if (! $payment->company_id && auth()->check()) {
                $payment->company_id = auth()->user()->company_id;
            }
        });

        static::saved(function (Payment $payment) {
            // Recalculate parent invoice
            $invoice = $payment->invoice()->withoutGlobalScope('company')->first();
            if ($invoice) {
                $invoice->paid_total = $invoice->payments()->sum('amount');
                $invoice->refreshStatus();
                $invoice->saveQuietly();
            }
        });

        static::deleted(function (Payment $payment) {
            $invoice = $payment->invoice()->withoutGlobalScope('company')->first();
            if ($invoice) {
                $invoice->paid_total = $invoice->payments()->sum('amount');
                $invoice->refreshStatus();
                $invoice->saveQuietly();
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
