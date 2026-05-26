<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'customer_id', 'number', 'reference', 'status',
        'is_credit', 'credits_invoice_id',
        'invoice_date', 'due_date', 'payment_terms',
        'customer_name', 'customer_address_line', 'customer_postal_code',
        'customer_city', 'customer_country', 'customer_vat_number',
        'customer_kvk_number', 'customer_email',
        'subtotal', 'vat_total', 'total', 'paid_total', 'vat_breakdown',
        'notes', 'footer',
        'sent_at', 'first_viewed_at', 'paid_at',
        'incasso_sent_at', 'incasso_reference', 'incasso_handler', 'incasso_phase',
    ];

    protected $casts = [
        'is_credit' => 'boolean',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'first_viewed_at' => 'datetime',
        'paid_at' => 'datetime',
        'incasso_sent_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'vat_total' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'vat_breakdown' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('invoices.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (Invoice $invoice) {
            if (! $invoice->company_id && auth()->check()) {
                $invoice->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class)->withoutGlobalScope('company'); }
    public function lines(): HasMany { return $this->hasMany(InvoiceLine::class)->orderBy('sort_order'); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function attachments(): MorphMany { return $this->morphMany(Attachment::class, 'attachable'); }
    public function creditNotes(): HasMany { return $this->hasMany(Invoice::class, 'credits_invoice_id'); }
    public function originalInvoice(): BelongsTo { return $this->belongsTo(Invoice::class, 'credits_invoice_id')->withoutGlobalScope('company'); }

    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->total - (float) $this->paid_total;
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->is_credit) return false;
        return in_array($this->status, ['sent', 'partial'])
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->is_overdue) return 0;
        return (int) $this->due_date->diffInDays(now());
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['sent', 'partial', 'overdue', 'incasso']);
    }

    public function scopeRegular(Builder $query): Builder
    {
        return $query->where('is_credit', false);
    }

    public function scopeCredit(Builder $query): Builder
    {
        return $query->where('is_credit', true);
    }

    public function scopeForStatus(Builder $query, ?string $status): Builder
    {
        if (! $status || $status === 'all') return $query;
        if ($status === 'creditnota') return $query->where('is_credit', true);
        return $query->where('is_credit', false)->where('status', $status);
    }

    public function refreshStatus(): void
    {
        if ($this->is_credit) return;
        if (in_array($this->status, ['draft', 'cancelled', 'incasso'])) return;

        $paid = (float) $this->paid_total;
        $total = (float) $this->total;

        if ($paid >= $total && $total > 0) {
            $this->status = 'paid';
            if (! $this->paid_at) $this->paid_at = now();
        } elseif ($paid > 0) {
            $this->status = 'partial';
        } elseif ($this->due_date && $this->due_date->isPast()) {
            $this->status = 'overdue';
        } else {
            $this->status = 'sent';
        }
    }
}
