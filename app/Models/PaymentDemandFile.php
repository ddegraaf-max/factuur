<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kopie van de factuur bij een losse online aanmaning. De klant krijgt haar
 * als bijlage bij de mail en kan haar openen vanaf de pagina van de aanmaning:
 * geen "wij hebben de factuur nooit ontvangen" meer.
 */
class PaymentDemandFile extends Model
{
    public const MIME_TYPES = ['application/pdf', 'image/png', 'image/jpeg'];

    public const MAX_KB = 8192;

    protected $fillable = ['payment_demand_id', 'filename', 'mime_type', 'size_bytes', 'file_data'];

    protected $hidden = ['file_data'];

    public function demand(): BelongsTo
    {
        return $this->belongsTo(PaymentDemand::class, 'payment_demand_id')->withoutGlobalScope('company');
    }

    public function contents(): ?string
    {
        $data = base64_decode((string) $this->file_data, true);

        return $data === false || $data === '' ? null : $data;
    }
}
