<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id', 'product_id', 'sort_order',
        'description', 'details', 'quantity', 'unit',
        'unit_price', 'vat_rate', 'discount_pct',
        'line_subtotal', 'line_vat', 'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'discount_pct' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'line_vat' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function quote(): BelongsTo { return $this->belongsTo(Quote::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
