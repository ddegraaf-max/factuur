<?php

namespace App\Support;

use App\Models\User;

/**
 * Wie mag de interne eigenaarspagina's zien (marketing-inzichten, merkbewaking)?
 * E-mailadressen uit MARKETING_STATS_EMAILS, of — zolang die leeg is — gebruiker 1.
 */
class OwnerAccess
{
    public static function allows(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $allowed = collect(explode(',', (string) config('services.marketing_stats.emails')))
            ->map(fn ($email) => mb_strtolower(trim($email)))
            ->filter();

        return $allowed->isNotEmpty()
            ? $allowed->contains(mb_strtolower($user->email))
            : $user->id === static::owner()?->id;
    }

    /**
     * De eigenaar: de allereerste gebruiker. Bewust "laagste id" en niet
     * letterlijk 1 — in tests (Postgres-sequences lopen door na een rollback)
     * is de eerste gebruiker niet altijd id 1.
     */
    public static function owner(): ?User
    {
        $configured = collect(explode(',', (string) config('services.marketing_stats.emails')))
            ->map(fn ($email) => mb_strtolower(trim($email)))
            ->filter();

        if ($configured->isNotEmpty()) {
            $user = User::query()->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(email)'), $configured->all())->orderBy('id')->first();
            if ($user) {
                return $user;
            }
        }

        // Geen adres ingesteld: de eigenaar is de (eerste) gebruiker van een
        // vrijgestelde administratie (is_exempt — alleen EasyInvoice zelf
        // betaalt niet), en pas daarna de allereerste gebruiker überhaupt.
        $exemptOwner = User::query()
            ->whereHas('company', fn ($q) => $q->withoutGlobalScope('company')->where('is_exempt', true))
            ->orderBy('id')
            ->first();

        return $exemptOwner ?? User::query()->orderBy('id')->first();
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
}
