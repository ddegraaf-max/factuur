<?php

namespace App\Services\Fachora;

use App\Models\Provider;

/**
 * De ene regel die overal geldt: Fachora mailt of sms't een bedrijf nooit
 * ongevraagd. De Poolse wet op elektronische communicatie (PKE, sinds
 * november 2024) verbiedt elektronische direct marketing zonder voorafgaande
 * toestemming, ook richting bedrijven. Een bedrijf uit een openbaar register
 * hoort daarom pas iets van ons als het zelf zijn profiel heeft geclaimd, of
 * als het op een gekochte opt-in-lijst staat waarvan de toestemming is
 * vastgelegd (consent_at + consent_source). Een afmelding (email_blocked_at)
 * wint altijd.
 *
 * Werving zonder e-mail: het openbare profiel met "Przejmij profil", brieven
 * per post met een claimcode (App\Services\Fachora\LetterService), de
 * landingspagina voor bedrijven en de opt-in-lijsten.
 */
class ConsentPolicy
{
    public static function mayEmail(?Provider $provider): bool
    {
        return $provider !== null
            && filled($provider->email)
            && $provider->email_blocked_at === null
            && ($provider->claimed_at !== null || $provider->consent_at !== null);
    }

    public static function maySms(?Provider $provider): bool
    {
        return $provider !== null
            && filled($provider->phone)
            && $provider->email_blocked_at === null
            && ($provider->claimed_at !== null || $provider->consent_at !== null);
    }

    /** Waarom dit bedrijf (niet) gemaild mag worden, voor de eigenaarspagina. */
    public static function reason(Provider $provider): string
    {
        if ($provider->email_blocked_at) {
            return __('Afgemeld op :date', ['date' => $provider->email_blocked_at->translatedFormat('j F Y')]);
        }
        if (blank($provider->email)) {
            return __('Geen e-mailadres');
        }
        if ($provider->claimed_at) {
            return __('Profiel geclaimd op :date', ['date' => $provider->claimed_at->translatedFormat('j F Y')]);
        }
        if ($provider->consent_at) {
            return __('Opt-in (:source) op :date', ['source' => $provider->consent_source ?: '?', 'date' => $provider->consent_at->translatedFormat('j F Y')]);
        }

        return __('Geen toestemming: alleen per post of via het profiel');
    }

    /** @throws \DomainException */
    public static function assertMayEmail(Provider $provider): void
    {
        if (! self::mayEmail($provider)) {
            throw new \DomainException(__('Dit bedrijf heeft geen toestemming gegeven voor e-mail: :reason', ['reason' => self::reason($provider)]));
        }
    }
}
