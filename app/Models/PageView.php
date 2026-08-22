<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eén bezochte marketingpagina. Privacyvriendelijk: geen IP, geen cookie —
 * alleen een dagelijks wisselende hash om unieke bezoekers per dag te tellen.
 */
class PageView extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'viewed_on' => 'date',
    ];
}
