<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén regel in het logboek van een online aanmaning: verstuurd, geopend,
 * gereageerd, overgedragen. Met tijdstip en, bij wat de klant doet, het
 * IP-adres — het bewijs dat meegaat in het dossier.
 */
class PaymentDemandEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['payment_demand_id', 'event', 'actor', 'description', 'ip_address', 'user_agent', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function demand(): BelongsTo
    {
        return $this->belongsTo(PaymentDemand::class, 'payment_demand_id')->withoutGlobalScope('company');
    }
}
