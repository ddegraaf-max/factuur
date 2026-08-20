<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén aangeleverd bestand in het Postvak IN van de inkoop: een bijlage uit
 * een e-mail die naar het inboek-adres van de administratie is gestuurd.
 * Vanuit het postvak wordt het ingeboekt (gekoppeld aan een inkoopfactuur)
 * of afgewezen.
 */
class PurchaseInboxItem extends Model
{
    protected $fillable = [
        'company_id', 'purchase_invoice_id',
        'from_email', 'subject', 'filename', 'mime_type', 'size_bytes',
        'file_data', 'status', 'received_at',
        'scan', 'scan_error', 'scanned_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'received_at' => 'datetime',
        'scan' => 'array',
        'scanned_at' => 'datetime',
    ];

    // Het bestand zelf is te zwaar om standaard mee te sturen.
    protected $hidden = ['file_data'];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('purchase_inbox_items.company_id', auth()->user()->company_id);
            }
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function purchaseInvoice(): BelongsTo { return $this->belongsTo(PurchaseInvoice::class)->withoutGlobalScope('company'); }

    public function contents(): ?string
    {
        return $this->file_data ? base64_decode($this->file_data) : null;
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
