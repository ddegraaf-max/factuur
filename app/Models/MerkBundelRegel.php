<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Een bundel of Pingen-maand van een merk die op een factuur staat.
 * Uniek op merk + klant_kenmerk + bundel_id: nooit twee keer gefactureerd.
 */
class MerkBundelRegel extends Model
{
    protected $table = 'merk_bundel_regels';

    protected $guarded = ['id'];

    protected $casts = ['afgemeld_op' => 'datetime', 'bedrag_excl_cent' => 'integer'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withoutGlobalScope('company');
    }
}
