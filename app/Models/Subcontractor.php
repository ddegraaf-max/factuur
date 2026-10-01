<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bedrijf in de pool van onderaannemers: kan één of meer werkpakketten
 * uitvoeren en krijgt per uitvraagronde een eigen aanvraag met tokenlink.
 */
class Subcontractor extends Model
{
    protected $fillable = [
        'company_id', 'name', 'contact_name', 'email', 'phone', 'city', 'website', 'notes', 'source', 'archived_at',
    ];

    protected $casts = ['archived_at' => 'datetime'];

    /** Alleen bedrijven die in de pool staan (niet uit de pool gehaald). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('subcontractors.archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('subcontractors.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (Subcontractor $subcontractor) {
            if (! $subcontractor->company_id && auth()->check()) {
                $subcontractor->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function workPackages(): BelongsToMany
    {
        return $this->belongsToMany(WorkPackage::class)->orderBy('sort_order');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(TenderRequest::class);
    }
}
