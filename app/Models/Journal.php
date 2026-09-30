<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Een dagboek: verkoop, inkoop, bank, kas of memoriaal.
 *
 * Waarom niet één stapel boekingen met een soort erop: het boekstuknummer.
 * VRK 2026-0043 zegt meteen dat het de drieënveertigste verkoopboeking van dit
 * jaar is. Bij één doorlopende reeks zou je bij een gat in de nummering moeten
 * uitzoeken of er een factuur, een betaling of een correctie is verdwenen.
 */
class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'code', 'name', 'kind', 'ledger_account_id',
        'is_system', 'active', 'sort',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'active' => 'boolean',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('journals.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (Journal $journal) {
            if (! $journal->company_id && auth()->check()) {
                $journal->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** De vaste tegenrekening: bij bank de bankrekening, bij kas de kas. */
    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class)->withoutGlobalScope('company');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}
