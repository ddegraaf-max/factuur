<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** De laatste klantscore van een klant, met de signalen en de bronnen erachter. */
class CustomerCheck extends Model
{
    protected $fillable = ['company_id', 'customer_id', 'score', 'grade', 'signals', 'sources', 'checked_at', 'sources_checked_at'];

    protected $casts = [
        'score' => 'integer',
        'signals' => 'array',
        'sources' => 'array',
        'checked_at' => 'datetime',
        'sources_checked_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
