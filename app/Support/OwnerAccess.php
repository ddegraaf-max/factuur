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

        return $allowed->isNotEmpty() ? $allowed->contains(mb_strtolower($user->email)) : $user->id === 1;
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

        $owner = User::find(1);

        return $owner ? [$owner->email] : [];
    }
}
