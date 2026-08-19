<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringInvoice extends Model
{
    use HasFactory;

    public const FREQUENCIES = [
        'weekly'     => 'Wekelijks',
        'monthly'    => 'Maandelijks',
        'quarterly'  => 'Per kwartaal',
        'halfyearly' => 'Per half jaar',
        'yearly'     => 'Jaarlijks',
    ];

    protected $fillable = [
        'company_id', 'customer_id', 'brand_profile_id', 'source_invoice_id',
        'frequency', 'start_date', 'next_run_on', 'end_date',
        'auto_send', 'active',
        'reference', 'notes', 'payment_terms', 'lines',
        'last_run_on', 'invoices_generated',
    ];

    protected $casts = [
        'start_date' => 'date',
        'next_run_on' => 'date',
        'end_date' => 'date',
        'last_run_on' => 'date',
        'auto_send' => 'boolean',
        'active' => 'boolean',
        'payment_terms' => 'integer',
        'invoices_generated' => 'integer',
        'lines' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('recurring_invoices.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (RecurringInvoice $recurring) {
            if (! $recurring->company_id && auth()->check()) {
                $recurring->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class)->withoutGlobalScope('company'); }
    public function sourceInvoice(): BelongsTo { return $this->belongsTo(Invoice::class, 'source_invoice_id')->withoutGlobalScope('company'); }

    public function getFrequencyLabelAttribute(): string
    {
        return self::FREQUENCIES[$this->frequency] ?? $this->frequency;
    }

    /**
     * De eerstvolgende datum ná $current volgens de frequentie.
     * Maand-gebaseerde frequenties houden vast aan de dag van start_date
     * (bijv. gestart op de 31e: feb → 28e, maart → weer 31e).
     */
    public function nextDateAfter(Carbon $current): Carbon
    {
        return match ($this->frequency) {
            'weekly'     => $current->copy()->addWeek(),
            'quarterly'  => $this->addMonthsAnchored($current, 3),
            'halfyearly' => $this->addMonthsAnchored($current, 6),
            'yearly'     => $this->addMonthsAnchored($current, 12),
            default      => $this->addMonthsAnchored($current, 1), // monthly
        };
    }

    protected function addMonthsAnchored(Carbon $date, int $months): Carbon
    {
        $anchorDay = (int) ($this->start_date?->day ?? $date->day);
        $base = $date->copy()->startOfMonth()->addMonths($months);

        return $base->day(min($anchorDay, $base->daysInMonth))->startOfDay();
    }
}
