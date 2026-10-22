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
            // Ook wie op een aanbieding akkoord ging met de voorwaarden: berichten over opdrachten, geen reclame.
            && ($provider->claimed_at !== null || $provider->consent_at !== null || $provider->terms_accepted_at !== null);
    }

    /**
     * Mag een concrete aanvraag van een klant naar dit bedrijf (1.86)? Ook
     * zonder claim of opt-in: de klant koos dit bedrijf zelf voor zijn klus,
     * en het bericht gaat over die klus, niet over Fachora (geen direct
     * marketing in de zin van de PKE). Per aanvraag maximaal max_offers
     * bedrijven; elk bericht draagt de afmeldlink en de art. 14-informatie.
     * Een afmelding of blokkade wint altijd.
     */
    public static function mayEmailRequest(?Provider $provider): bool
    {
        if (self::mayEmail($provider)) {
            return true;
        }

        return $provider !== null
            && (bool) config('fachora.customer_choice', true)
            && filled($provider->email)
            && $provider->email_blocked_at === null
            && ! in_array($provider->status, ['blocked', 'suspended'], true);
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
        if ($provider->terms_accepted_at) {
            return __('Voorwaarden geaccepteerd op een aanbieding (:date): alleen berichten over opdrachten', ['date' => $provider->terms_accepted_at->translatedFormat('j F Y')]);
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
