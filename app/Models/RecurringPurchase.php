<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Terugkerende inkoop ("vaste lasten"): een profiel dat periodiek een
 * inkoopfactuur inboekt — dezelfde plannings- en inhaallogica als de
 * terugkerende verkoopfacturen.
 */
class RecurringPurchase extends Model
{
    public const FREQUENCIES = [
        'weekly'     => 'Wekelijks',
        'monthly'    => 'Maandelijks',
        'quarterly'  => 'Per kwartaal',
        'halfyearly' => 'Per half jaar',
        'yearly'     => 'Jaarlijks',
    ];

    protected $fillable = [
        'company_id', 'supplier_name', 'category',
        'frequency', 'start_date', 'next_run_on', 'end_date', 'active',
        'vat_lines', 'auto_paid', 'payment_method', 'notes',
        'last_run_on', 'purchases_generated',
    ];

    protected $casts = [
        'start_date' => 'date',
        'next_run_on' => 'date',
        'end_date' => 'date',
        'last_run_on' => 'date',
        'active' => 'boolean',
        'auto_paid' => 'boolean',
        'vat_lines' => 'array',
        'purchases_generated' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('recurring_purchases.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (RecurringPurchase $recurring) {
            if (! $recurring->company_id && auth()->check()) {
                $recurring->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }

    public function getFrequencyLabelAttribute(): string
    {
        return self::FREQUENCIES[$this->frequency] ?? $this->frequency;
    }

    /** Totaal incl. BTW van één periode (uit de regels). */
    public function totalAmount(): float
    {
        return round(collect($this->vat_lines ?? [])
            ->sum(fn ($l) => (float) ($l['base'] ?? 0) + (float) ($l['vat'] ?? 0)), 2);
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
