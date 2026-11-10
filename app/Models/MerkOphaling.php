<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Eén ophaling van extra verbruik bij een merk (zie MerkenService). */
class MerkOphaling extends Model
{
    protected $table = 'merk_ophalingen';

    protected $guarded = ['id'];

    protected $casts = ['klaar_op' => 'datetime'];
}
