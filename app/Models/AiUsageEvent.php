<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * Eén AI-actie (bonscan of offerteherkenning) per regel — voor inzicht in
 * het gebruik per administratie, fair use en kostenbewaking.
 */
class AiUsageEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['company_id', 'kind', 'source', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Registreer een AI-actie. Mag nooit de functie zelf laten falen. */
    public static function record(int $companyId, string $kind, ?string $source = null): void
    {
        try {
            static::create([
                'company_id' => $companyId,
                'kind' => $kind,
                'source' => $source,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AI-gebruik registreren mislukt', ['company' => $companyId, 'kind' => $kind, 'error' => $e->getMessage()]);
        }
    }
}
