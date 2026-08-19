<?php

namespace App\Models\Concerns;

use App\Models\BrandProfile;
use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Voor documenten (facturen, offertes) die onder een handelsnaam kunnen
 * worden verstuurd. Levert de relatie en een niet-opgeslagen kopie van de
 * bedrijfsgegevens met de huisstijl van het gekozen profiel eroverheen.
 */
trait HasBrandProfile
{
    public function brandProfile(): BelongsTo
    {
        return $this->belongsTo(BrandProfile::class)->withoutGlobalScope('company');
    }

    /**
     * De bedrijfsgegevens zoals ze op dít document horen: is er een
     * handelsnaam gekozen, dan gaan naam, logo, kleur en sjabloon daarvan
     * eroverheen. Juridische velden (KvK, BTW-nummer, IBAN, adres) blijven
     * altijd van het bedrijf zelf. De kopie wordt nooit opgeslagen.
     */
    public function brandedCompany(): ?Company
    {
        $company = $this->company;
        $profile = $this->brandProfile;
        if (! $company || ! $profile) {
            return $company;
        }

        $branded = $company->replicate();
        $branded->name = $profile->name;
        // Eigen logo van de handelsnaam — heeft die er geen, dan bewust ook
        // niet het logo van het hoofdbedrijf (verkeerd merk op het document).
        $branded->logo_data = $profile->logo_data;
        $branded->logo_path = null;
        $branded->logo_scale = $profile->logo_scale ?? 100;
        if ($profile->brand_color) {
            $branded->brand_color = $profile->brand_color;
        }
        if ($profile->invoice_template) {
            $branded->invoice_template = $profile->invoice_template;
        }
        if (filled($profile->invoice_footer)) {
            $branded->invoice_footer = $profile->invoice_footer;
        }

        return $branded;
    }
}
