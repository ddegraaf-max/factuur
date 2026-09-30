<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén regel van een journaalpost: een bedrag, op één rekening, aan één kant.
 *
 * De bedragen staan in hele centen. Debet moet exact gelijk zijn aan credit, en
 * "exact" bestaat niet bij een getal dat onderweg is afgerond.
 */
class JournalLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'journal_entry_id', 'ledger_account_id', 'description',
        'debit_cents', 'credit_cents', 'vat_rate', 'vat_cents',
        'customer_id', 'supplier_name', 'sort',
    ];

    protected $casts = [
        'debit_cents' => 'integer',
        'credit_cents' => 'integer',
        'vat_cents' => 'integer',
        'vat_rate' => 'decimal:2',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('journal_lines.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (JournalLine $line) {
            if (! $line->company_id && auth()->check()) {
                $line->company_id = auth()->user()->company_id;
            }
        });
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id')->withoutGlobalScope('company');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id')->withoutGlobalScope('company');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withoutGlobalScope('company');
    }

    /** Het bedrag met een teken: debet positief, credit negatief. */
    public function signedCents(): int
    {
        return $this->debit_cents - $this->credit_cents;
    }
}
