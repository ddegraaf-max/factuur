<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Wie mag de interne eigenaarspagina's zien (marketing-inzichten, merkbewaking,
 * administraties)? De adressen uit MARKETING_STATS_EMAILS, anders de eerste
 * gebruiker van een vrijgestelde administratie (is_exempt: het platform zelf).
 *
 * Demogebruikers zijn nooit eigenaar — op een verse omgeving (zoals Lopra) is
 * de demogebruiker anders de "eerste gebruiker". En in productie is er bewust
 * géén terugval op de allereerste gebruiker überhaupt: de eerste klant die zich
 * registreert mag niet per ongeluk alle administraties zien.
 */
class OwnerAccess
{
    public static function allows(?User $user): bool
    {
        if (! $user || self::isDemo($user)) {
            return false;
        }

        $allowed = self::configured();

        return $allowed->isNotEmpty()
            ? $allowed->contains(mb_strtolower($user->email))
            : $user->id === static::owner()?->id;
    }

    /**
     * De eigenaar. Bewust "laagste id" en niet letterlijk 1 — in tests
     * (Postgres-sequences lopen door na een rollback) is de eerste gebruiker
     * niet altijd id 1.
     */
    public static function owner(): ?User
    {
        $configured = self::configured();

        if ($configured->isNotEmpty()) {
            $user = User::query()
                ->whereIn(DB::raw('LOWER(email)'), $configured->all())
                ->orderBy('id')
                ->first();
            if ($user) {
                return $user;
            }
        }

        // Geen adres ingesteld: de (eerste) gebruiker van een vrijgestelde
        // administratie — alleen het platform zelf betaalt niet.
        $exemptOwner = User::query()
            ->whereHas('company', fn (Builder $q) => $q->withoutGlobalScope('company')->where('is_exempt', true)->where('is_demo', false))
            ->orderBy('id')
            ->first();

        if ($exemptOwner || app()->isProduction()) {
            return $exemptOwner;
        }

        // Alleen buiten productie (tests, lokale preview): de allereerste echte gebruiker.
        return User::query()
            ->whereHas('company', fn (Builder $q) => $q->withoutGlobalScope('company')->where('is_demo', false))
            ->orderBy('id')
            ->first();
    }

    /** Adressen waar eigenaarsmail (dossiers) naartoe gaat. */
    public static function emails(): array
    {
        $configured = collect(explode(',', (string) config('services.marketing_stats.emails')))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->values()
            ->all();

        if ($configured) {
            return $configured;
        }

        $owner = static::owner();

        return $owner ? [$owner->email] : [];
    }

    private static function configured(): \Illuminate\Support\Collection
    {
        return collect(explode(',', (string) config('services.marketing_stats.emails')))
            ->map(fn ($email) => mb_strtolower(trim($email)))
            ->filter()
            ->values();
    }

    private static function isDemo(User $user): bool
    {
        return (bool) $user->company?->is_demo;
    }
}
