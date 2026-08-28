<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Een via Ponto geautoriseerde bankrekening. */
class PontoAccount extends Model
{
    protected $fillable = [
        'company_id', 'ponto_connection_id', 'ponto_id', 'iban', 'name', 'holder_name', 'bank_name',
        'currency', 'current_balance', 'available_balance', 'sync_enabled',
        'authorized_at', 'authorization_expires_at', 'last_synced_at', 'last_error',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'sync_enabled' => 'boolean',
        'authorized_at' => 'datetime',
        'authorization_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function connection(): BelongsTo { return $this->belongsTo(PontoConnection::class, 'ponto_connection_id'); }
    public function transactions(): HasMany { return $this->hasMany(BankTransaction::class)->withoutGlobalScope('company'); }

    /** Leesbare naam: IBAN met spaties, anders de omschrijving van de bank. */
    public function label(): string
    {
        return $this->iban ? trim(chunk_split($this->iban, 4, ' ')) : (string) ($this->name ?: 'Rekening');
    }
}
