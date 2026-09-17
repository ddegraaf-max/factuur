<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Werkpakket (fundering, houtskeletbouw, metselwerk …): het onderdeel van een
 * project waarvoor je bij onderaannemers een prijs opvraagt.
 */
class WorkPackage extends Model
{
    protected $fillable = ['company_id', 'name', 'description', 'sort_order'];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('work_packages.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (WorkPackage $package) {
            if (! $package->company_id && auth()->check()) {
                $package->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subcontractors(): BelongsToMany
    {
        return $this->belongsToMany(Subcontractor::class)->orderBy('name');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(TenderRound::class);
    }
}
