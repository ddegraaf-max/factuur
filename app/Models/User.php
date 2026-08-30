<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'company_id', 'role', 'locale',
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

    /** De ACTIEVE administratie (alle company-scoping leest dit veld). */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Alle administraties waarvan deze gebruiker lid is, met rol per administratie. */
    public function companies(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withPivot('role')->withTimestamps();
    }

    /** Is deze gebruiker lid van de administratie? */
    public function isMemberOf(Company $company): bool
    {
        return $this->companies()->whereKey($company->id)->exists();
    }

    /**
     * Wissel naar een andere administratie: het actieve bedrijf én de rol
     * dáárin worden op de gebruiker gezet, zodat alle bestaande scoping en
     * rolcontroles gewoon blijven werken.
     */
    public function switchToCompany(Company $company): bool
    {
        $membership = $this->companies()->whereKey($company->id)->first();
        if (! $membership) {
            return false;
        }

        $this->forceFill([
            'company_id' => $company->id,
            'role' => $membership->pivot->getAttribute('role') ?: 'staff',
        ])->save();

        return true;
    }

    /* ===================== ROLLEN ===================== */

    public const ROLES = ['owner', 'staff', 'accountant'];

    public const ROLE_LABELS = [
        'owner' => 'Beheerder',
        'staff' => 'Medewerker',
        'accountant' => 'Boekhouder (alleen inzien)',
    ];

    /** Beheerder: volledige toegang, inclusief instellingen, abonnement en team. */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /** Boekhouder: mag alles inzien en rapporten/exports gebruiken, niets wijzigen. */
    public function isAccountant(): bool
    {
        return $this->role === 'accountant';
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? ucfirst((string) ($this->role ?: 'eigenaar'));
    }

    /** Stuur de wachtwoord-reset e-mail in het Nederlands. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
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
