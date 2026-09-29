<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Eén verstuurde (of mislukte) sms. Het logboek laat zien wat er is verstuurd
 * en telt mee voor de grens per maand.
 */
class SmsMessage extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'recipient', 'sender', 'body', 'segments', 'status', 'provider_id', 'error',
        'subject_type', 'subject_id',
    ];

    protected $casts = [
        'segments' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
