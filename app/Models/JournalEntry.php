<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Een journaalpost: één boeking, met twee of meer regels die samen in balans
 * zijn. De database laat niets anders toe (zie de migratie).
 */
class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'journal_id', 'year', 'number', 'date', 'description',
        'source_type', 'source_id', 'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'year' => 'integer',
        'source_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('journal_entries.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (JournalEntry $entry) {
            if (! $entry->company_id && auth()->check()) {
                $entry->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class)->withoutGlobalScope('company');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)->orderBy('sort')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Het totaal van de debetzijde in centen; gelijk aan de creditzijde. */
    public function totalCents(): int
    {
        return (int) $this->lines->sum('debit_cents');
    }

    /**
     * Klopt deze post? Hoort altijd true te zijn — de database laat het niet
     * anders toe. Bestaat voor de controle-opdracht (ledger:check), die dit
     * over de hele administratie nagaat zonder op de database te vertrouwen.
     */
    public function isBalanced(): bool
    {
        return (int) $this->lines->sum('debit_cents') === (int) $this->lines->sum('credit_cents');
    }
}
