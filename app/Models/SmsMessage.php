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
        'subject_type', 'subject_id', 'delivery_status', 'delivery_code', 'delivery_detail', 'delivered_at',
    ];


    /** De afleverstatus in gewone taal, of null als Smstools nog niets heeft gemeld. */
    public function deliveryLabel(): ?string
    {
        return match ($this->delivery_status) {
            'delivered' => __('afgeleverd'),
            'failed' => __('niet afgeleverd'),
            'pending' => __('onderweg'),
            'unknown' => __('aflevering onbekend'),
            default => null,
        };
    }

    protected $casts = [
        'delivered_at' => 'datetime',
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
