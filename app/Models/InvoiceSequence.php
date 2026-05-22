<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSequence extends Model
{
    protected $fillable = ['company_id', 'year', 'last_number'];

    protected $casts = [
        'year' => 'integer',
        'last_number' => 'integer',
    ];
}
