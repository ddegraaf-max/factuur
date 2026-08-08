<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderLog extends Model
{
    protected $fillable = [
        'company_id', 'invoice_id', 'type', 'kind', 'channel',
        'sent_to', 'amount_open', 'sent_at',
    ];

    protected $casts = [
        'amount_open' => 'decimal:2',
        'sent_at' => 'datetime',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
}
