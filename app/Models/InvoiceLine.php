<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'product_id', 'sort_order',
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

    protected static function booted(): void
    {
        /*
         * Een factuurregel die verandert, verandert de boeking.
         *
         * Dit moet hier hangen en niet alleen bij Invoice::saved. Een factuur
         * wordt eerst opgeslagen en krijgt daarna haar regels; op het moment van
         * dat eerste opslaan is er dus nog niets te boeken. Zonder deze haak
         * belandt een factuur die daarna niet opnieuw wordt opgeslagen nooit in
         * het grootboek — en dat was op de live demo precies het geval: vier van
         * de elf facturen ontbraken, terwijl de proefbalans netjes sloot. Een
         * boekhouding die sluit maar niet compleet is, is het ergste soort fout:
         * er is niets aan te zien.
         *
         * De service boekt een gewijzigde factuur opnieuw en laat een
         * ongewijzigde met rust, dus drie regels achter elkaar toevoegen levert
         * één kloppende boeking op.
         */
        $bijwerken = function (InvoiceLine $line) {
            $invoice = $line->invoice()->withoutGlobalScope('company')->first();
            if ($invoice) {
                app(\App\Services\LedgerPostingService::class)->sync($invoice);
            }
        };

        static::saved($bijwerken);
        static::deleted($bijwerken);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withoutGlobalScope('company');
    }
}
