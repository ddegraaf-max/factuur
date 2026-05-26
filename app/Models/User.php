<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'company_id', 'role',
        'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
        'verification_code', 'verification_code_expires_at',
        'verification_code_attempts', 'verification_code_sent_at',
    ];

    protected $hidden = [
        'password', 'remember_token',
        'two_factor_secret', 'two_factor_recovery_codes',
        'verification_code',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'verification_code_expires_at' => 'datetime',
        'verification_code_sent_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    public function recoveryCodes(): array
    {
        if (! $this->two_factor_recovery_codes) return [];
        return json_decode(decrypt($this->two_factor_recovery_codes), true) ?? [];
    }

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    public function generateVerificationCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(15),
            'verification_code_attempts' => 0,
            'verification_code_sent_at' => now(),
        ])->save();

        return $code;
    }

    public function markEmailAsVerified(): void
    {
        $this->forceFill([
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
            'verification_code_attempts' => 0,
        ])->save();
    }

    public function verificationCodeIsValid(string $code): bool
    {
        if (! $this->verification_code || ! $this->verification_code_expires_at) {
            return false;
        }
        if (now()->greaterThan($this->verification_code_expires_at)) {
            return false;
        }
        return hash_equals($this->verification_code, $code);
    }
}
