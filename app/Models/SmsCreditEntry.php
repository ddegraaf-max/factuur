<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eén boeking op het sms-tegoed: bij (aankoop) of af (verstuurde sms). */
class SmsCreditEntry extends Model
{
    protected $fillable = ['company_id', 'amount', 'kind', 'sms_purchase_id', 'sms_message_id', 'note'];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
