<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Een boekjaar, open of vastgesteld.
 *
 * Vastgesteld betekent dicht: er kan niets meer in geboekt worden, ook niet
 * door een import of een correctie. Dat wordt door de database afgedwongen
 * (zie de migratie), niet door een controle die iemand kan overslaan — want
 * anders betekent een jaarrekening niets en klopt de aangifte die al is
 * ingediend niet meer met de boeken.
 */
class BookYear extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'year', 'status', 'closed_at', 'closed_by'];

    protected $casts = [
        'year' => 'integer',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('book_years.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (BookYear $year) {
            if (! $year->company_id && auth()->check()) {
                $year->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
