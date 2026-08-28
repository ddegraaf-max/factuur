<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Ponto-koppeling van een administratie: OAuth-tokens (versleuteld) en status. */
class PontoConnection extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_NEEDS_REAUTH = 'needs_reauth';

    protected $fillable = [
        'company_id', 'access_token', 'refresh_token', 'token_expires_at', 'status', 'scope',
        'sandbox', 'connected_at', 'last_synced_at', 'last_error',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'sandbox' => 'boolean',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function accounts(): HasMany { return $this->hasMany(PontoAccount::class); }

    public function needsReauthorization(): bool
    {
        return $this->status === self::STATUS_NEEDS_REAUTH;
    }
}
