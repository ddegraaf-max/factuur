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
        'subtotal', 'vat_total', 'total', 'vat_lines', 'deductions', 'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'date',
        'subtotal' => 'decimal:2',
        'vat_total' => 'decimal:2',
        'total' => 'decimal:2',
        'vat_lines' => 'array',
        'deductions' => 'array',
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

    /**
     * Zoek een waarschijnlijk al ingeboekte dubbel: eerst op het factuurnummer
     * van de leverancier (sterk signaal), anders op leverancier + totaalbedrag.
     * Werkt binnen de company-scope van de ingelogde gebruiker.
     */
    public static function findLikelyDuplicate(?string $reference, ?string $supplier, ?float $totalIncl): ?self
    {
        if (filled($reference)) {
            $match = static::whereRaw('LOWER(supplier_reference) = ?', [mb_strtolower(trim($reference))])
                ->latest('id')->first();
            if ($match) {
                return $match;
            }
        }

        if (filled($supplier) && $totalIncl > 0) {
            return static::whereRaw('LOWER(supplier_name) = ?', [mb_strtolower(trim($supplier))])
                ->where('total', round($totalIncl, 2))
                ->latest('id')->first();
        }

        return null;
    }

    /** Waarschuwingstekst wanneer deze factuur als mogelijke dubbel is gevonden. */
    public function duplicateWarningText(): string
    {
        return sprintf(
            'Let op: deze factuur lijkt al ingeboekt — %s van %s (%s, € %s incl. btw).',
            $this->supplier_reference ? "factuurnummer {$this->supplier_reference}" : 'een factuur',
            $this->supplier_name,
            $this->invoice_date->format('d-m-Y'),
            number_format((float) $this->total, 2, ',', '.')
        );
    }

    /** Som van de verrekeningen (al ontvangen/ingehouden bedragen). */
    public function getDeductionsTotalAttribute(): float
    {
        return round(collect($this->deductions ?? [])->sum(fn ($d) => (float) ($d['amount'] ?? 0)), 2);
    }

    /** Wat er daadwerkelijk nog betaald moet worden aan de leverancier. */
    public function getPayableAttribute(): float
    {
        return round(max((float) $this->total - $this->deductions_total, 0), 2);
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
