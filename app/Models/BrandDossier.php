<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Maandelijks merkgebruik-dossier (bewijs van normaal gebruik van het merk). */
class BrandDossier extends Model
{
    protected $fillable = ['month', 'stats', 'manifest', 'mailed_to', 'generated_at'];

    protected $casts = ['stats' => 'array', 'manifest' => 'array', 'generated_at' => 'datetime'];
}
