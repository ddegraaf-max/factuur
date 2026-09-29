<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Online aanmaning bij één factuur: de laatste aanmaning vóór de deurwaarder.
 * De klant krijgt een geheime link naar een pagina met het bedrag van vandaag
 * (hoofdsom, wettelijke rente en na de termijn de incassokosten) en reageert
 * daar: betaald, ik betaal op, of ik ben het er niet mee eens.
 */
class PaymentDemand extends Model
{
    public const RESPONSES = ['paid', 'promise', 'dispute'];

    protected $fillable = [
        'company_id', 'invoice_id', 'token', 'status', 'debtor_type', 'sent_to', 'principal', 'with_interest',
        'costs', 'costs_vat', 'term_days', 'deadline', 'sent_at', 'first_opened_at', 'response', 'response_date',
        'response_note', 'responded_at', 'expiry_notified_at', 'closed_at',
    ];

    protected $casts = [
        'with_interest' => 'boolean',
        'principal' => 'decimal:2',
        'costs' => 'decimal:2',
        'costs_vat' => 'decimal:2',
        'term_days' => 'integer',
        'deadline' => 'date:Y-m-d',
        'response_date' => 'date:Y-m-d',
        'sent_at' => 'datetime',
        'first_opened_at' => 'datetime',
        'responded_at' => 'datetime',
        'expiry_notified_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('payment_demands.company_id', auth()->user()->company_id);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withoutGlobalScope('company');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentDemandEvent::class)->orderBy('created_at')->orderBy('id');
    }

    /** De geheime link uit de mail: de pagina met het bedrag van vandaag. */
    public function url(): string
    {
        return route('demand.show', $this->token);
    }

    public function isBusiness(): bool
    {
        return $this->debtor_type === 'business';
    }

    /** Loopt de aanmaning nog: verstuurd, niet ingetrokken, betaald of overgedragen. */
    public function isActive(): bool
    {
        return $this->status === 'sent';
    }

    /** De termijn is voorbij zodra de laatste dag om is. */
    public function isExpired(): bool
    {
        return $this->deadline->copy()->endOfDay()->isPast();
    }

    /** Klaar voor de deurwaarder: termijn voorbij en de aanmaning loopt nog. */
    public function isDue(): bool
    {
        return $this->isActive() && $this->isExpired();
    }
}
