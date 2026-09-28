<?php

namespace App\Models\Concerns;

/**
 * Documenten zonder btw-bedragen: btw verlegd of vrijgesteld onder de
 * kleineondernemersregeling. Sjablonen en schermen vragen de behandeling op
 * en hoeven de twee kolommen niet zelf te kennen.
 *
 * @property bool $vat_reversed
 * @property bool $vat_exempt
 */
trait HasVatTreatment
{
    /** 'exempt' (KOR), 'reversed' (btw verlegd) of null bij gewone btw. */
    public function vatTreatment(): ?string
    {
        if ($this->vat_exempt) {
            return 'exempt';
        }

        return $this->vat_reversed ? 'reversed' : null;
    }
}
