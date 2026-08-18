<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'supplier_name', 'supplier_reference', 'category',
        'invoice_date', 'due_date', 'status', 'paid_at', 'payment_method',
        'subtotal', 'vat_total', 'total', 'vat_lines', 'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'date',
        'subtotal' => 'decimal:2',
        'vat_total' => 'decimal:2',
        'total' => 'decimal:2',
        'vat_lines' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('purchase_invoices.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (PurchaseInvoice $purchase) {
            if (! $purchase->company_id && auth()->check()) {
                $purchase->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function attachments(): MorphMany { return $this->morphMany(Attachment::class, 'attachable'); }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'open'
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->is_overdue) return 0;
        return (int) $this->due_date->diffInDays(now());
    }
}
