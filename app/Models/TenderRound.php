<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uitvraagronde: één werkpakket van één project, uitgezet bij een handvol
 * bedrijven uit de pool. Open tot er gegund of gesloten wordt.
 */
class TenderRound extends Model
{
    public const STATUSES = ['open' => 'Open', 'awarded' => 'Gegund', 'closed' => 'Gesloten'];

    protected $fillable = [
        'company_id', 'quote_id', 'work_package_id', 'title', 'description', 'location',
        'start_week', 'deadline', 'budget', 'status', 'awarded_request_id', 'awarded_at',
    ];

    protected $casts = [
        'deadline' => 'date',
        'budget' => 'decimal:2',
        'awarded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('tender_rounds.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (TenderRound $round) {
            if (! $round->company_id && auth()->check()) {
                $round->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class)->withoutGlobalScope('company');
    }

    public function workPackage(): BelongsTo
    {
        return $this->belongsTo(WorkPackage::class)->withoutGlobalScope('company');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(TenderRequest::class)->orderBy('id');
    }

    public function awardedRequest(): BelongsTo
    {
        return $this->belongsTo(TenderRequest::class, 'awarded_request_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
