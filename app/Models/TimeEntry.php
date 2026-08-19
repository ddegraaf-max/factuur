<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén urenregel: gewerkte tijd (in minuten) op een datum, optioneel voor een
 * klant en/of project. Factureerbare regels worden met één klik gebundeld tot
 * een conceptfactuur; daarna verwijst invoice_id naar die factuur en is de
 * regel vergrendeld. Een regel met timer_started_at is een lopende timer.
 */
class TimeEntry extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'customer_id', 'invoice_id',
        'work_date', 'project', 'description', 'minutes',
        'hourly_rate', 'billable', 'timer_started_at',
    ];

    protected $casts = [
        'work_date' => 'date',
        'minutes' => 'integer',
        'hourly_rate' => 'decimal:2',
        'billable' => 'boolean',
        'timer_started_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('time_entries.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (TimeEntry $entry) {
            if (! $entry->company_id && auth()->check()) {
                $entry->company_id = auth()->user()->company_id;
            }
            if (! $entry->user_id && auth()->check()) {
                $entry->user_id = auth()->id();
            }
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class)->withoutGlobalScope('company'); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class)->withoutGlobalScope('company'); }

    /** Nog niet gefactureerd en geen lopende timer. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('invoice_id')->whereNull('timer_started_at');
    }

    /** Factureerbaar: open, aan een klant gekoppeld en op factureerbaar gezet. */
    public function scopeBillable(Builder $query): Builder
    {
        return $query->open()->where('billable', true)->whereNotNull('customer_id')->where('minutes', '>', 0);
    }

    public function getHoursAttribute(): float
    {
        return round($this->minutes / 60, 2);
    }

    /**
     * Het tarief dat voor deze regel geldt: eigen tarief → klanttarief →
     * standaardtarief van het bedrijf. Null wanneer nergens iets is ingesteld.
     */
    public function effectiveRate(): ?float
    {
        $rate = $this->hourly_rate
            ?? $this->customer?->hourly_rate
            ?? ($this->company ?? auth()->user()?->company)?->default_hourly_rate;

        return $rate !== null ? (float) $rate : null;
    }

    /** Factuurwaarde van deze regel (of null zonder bekend tarief). */
    public function amount(): ?float
    {
        $rate = $this->effectiveRate();

        return $rate !== null ? round(($this->minutes / 60) * $rate, 2) : null;
    }
}
