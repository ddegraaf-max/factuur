<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Bericht via het contactformulier van de website van een administratie. */
class SiteLead extends Model
{
    protected $fillable = ['company_id', 'name', 'email', 'phone', 'message', 'handled_at'];

    protected $casts = ['handled_at' => 'datetime'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
}
