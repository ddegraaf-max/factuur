<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén zakelijke rit: datum, van–naar, kilometers en tarief. Factureerbare
 * ritten met een klant worden met één klik gebundeld tot een conceptfactuur
 * (als reiskostenregels); daarna is de rit vergrendeld. Niet-factureerbare
 * ritten blijven in de administratie staan (kilometeradministratie voor de
 * eigen aangifte).
 */
class Trip extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'customer_id', 'invoice_id',
        'trip_date', 'from_location', 'to_location', 'round_trip',
        'description', 'kilometers', 'rate', 'billable',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'round_trip' => 'boolean',
        'kilometers' => 'decimal:1',
        'rate' => 'decimal:2',
        'billable' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('trips.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (Trip $trip) {
            if (! $trip->company_id && auth()->check()) {
                $trip->company_id = auth()->user()->company_id;
            }
            if (! $trip->user_id && auth()->check()) {
                $trip->user_id = auth()->id();
            }
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class)->withoutGlobalScope('company'); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class)->withoutGlobalScope('company'); }

    /** Nog niet gefactureerd. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('invoice_id');
    }

    /** Factureerbaar: open, aan een klant gekoppeld en op factureerbaar gezet. */
    public function scopeBillable(Builder $query): Builder
    {
        return $this->scopeOpen($query)->where('billable', true)->whereNotNull('customer_id')->where('kilometers', '>', 0);
    }

    /** Het geldende tarief: eigen tarief van de rit, anders het bedrijfstarief. */
    public function effectiveRate(): float
    {
        $rate = $this->rate
            ?? ($this->company ?? auth()->user()?->company)?->default_km_rate;

        return (float) ($rate ?? 0.23);
    }

    /** Factuurwaarde van deze rit. */
    public function amount(): float
    {
        return round((float) $this->kilometers * $this->effectiveRate(), 2);
    }

    /** Regelomschrijving zoals hij op de factuur komt. */
    public function lineDescription(): string
    {
        return 'Reiskosten: ' . $this->from_location . ' – ' . $this->to_location
            . ($this->round_trip ? ' (retour)' : '');
    }
}
