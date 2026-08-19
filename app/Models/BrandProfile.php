<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Een handelsnaam met eigen huisstijl. Het bedrijf blijft dezelfde juridische
 * entiteit (KvK, BTW-nummer, IBAN en factuurnummering veranderen niet), maar
 * de factuur toont de naam, het logo, de kleur, het sjabloon en de voetnoot
 * van de gekozen handelsnaam. Velden die leeg blijven vallen terug op de
 * standaard huisstijl van het bedrijf.
 */
class BrandProfile extends Model
{
    protected $fillable = [
        'company_id', 'name', 'logo_data', 'logo_scale',
        'brand_color', 'invoice_template', 'invoice_footer',
    ];

    protected $casts = [
        'logo_scale' => 'integer',
    ];

    // Zelfde afweging als bij Company: het logo (base64) is te zwaar om
    // standaard in elke response mee te sturen. makeVisible() waar nodig.
    protected $hidden = ['logo_data'];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('brand_profiles.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (BrandProfile $profile) {
            if (! $profile->company_id && auth()->check()) {
                $profile->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
}
