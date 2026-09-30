<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Een grootboekrekening.
 *
 * Het schema is een boom: hoofdrubriek (niveau 2) › rubriek (3) › rekening (4)
 * › subrekening (5). Op 2 en 3 wordt niet geboekt; die dragen de indeling van
 * de balans en de winst-en-verliesrekening. Zie App\Support\Rgs voor waar de
 * nummers en codes vandaan komen.
 */
class LedgerAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'number', 'name', 'rgs_code', 'side', 'statement',
        'level', 'parent_id', 'postable', 'is_system', 'active', 'sort',
    ];

    protected $casts = [
        'level' => 'integer',
        'postable' => 'boolean',
        'is_system' => 'boolean',
        'active' => 'boolean',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('ledger_accounts.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (LedgerAccount $account) {
            if (! $account->company_id && auth()->check()) {
                $account->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->withoutGlobalScope('company');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->withoutGlobalScope('company');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /** Staat deze rekening op de balans (en niet in het resultaat)? */
    public function isBalance(): bool
    {
        return $this->statement === 'balans';
    }

    /**
     * Het saldo zoals een mens het wil zien: positief als het aan de kant staat
     * waar deze rekening thuishoort.
     *
     * Een schuld van € 1.000 staat in het grootboek credit. Zou je dat als
     * -1.000 tonen, dan moet de gebruiker weten dat een schuld aan de
     * creditzijde hoort om te begrijpen dat hij niets tekort komt.
     */
    public function signedCents(int $debit, int $credit): int
    {
        return $this->side === 'C' ? $credit - $debit : $debit - $credit;
    }
}
