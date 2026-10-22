<?php

namespace App\Services\Fachora;

use App\Mail\FachoraMail;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Models\ServiceJobOffer;
use App\Support\DocumentLocale;
use App\Support\OwnerAccess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Alle mail van Fachora loopt hierlangs: aan de klant (in zijn taal), aan
 * een bedrijf (alleen met toestemming, zie ConsentPolicy) en aan de eigenaar
 * (in het Nederlands). Een mislukte mail breekt nooit de handeling zelf.
 */
class Notifier
{
    public function customer(ServiceJob $job, string $kind, array $extra = []): bool
    {
        if ($job->isDemo()) {
            return false; // demo-klanten bestaan niet
        }

        return $this->send($job->customer_email, $this->customerLocale($job), new FachoraMail($kind, 'customer', $job, null, $job->provider, $extra));
    }

    /** Aan een bedrijf: alleen als het zelf om berichten heeft gevraagd (geclaimd of opt-in). */
    public function provider(Provider $provider, string $kind, array $extra = [], ?ServiceJob $job = null, ?ServiceJobOffer $offer = null): bool
    {
        if ($provider->isDemo() || $job?->isDemo()) {
            return false;
        }

        if (! ConsentPolicy::mayEmail($provider)) {
            Log::info('Fachora: geen mail aan bedrijf zonder toestemming', ['provider' => $provider->id, 'kind' => $kind]);

            return false;
        }

        return $this->send($provider->email, $this->providerLocale($provider), new FachoraMail($kind, 'provider', $job, $offer, $provider, $extra));
    }

    /**
     * Een aanvraag die de klant zelf naar dit bedrijf stuurde (1.86). Ook
     * zonder claim of opt-in (ConsentPolicy::mayEmailRequest): het bericht gaat
     * over de klus van die klant, met afmeldlink en de art. 14-informatie.
     */
    public function providerRequest(Provider $provider, ServiceJob $job, ServiceJobOffer $offer): bool
    {
        if ($provider->isDemo() || $job->isDemo()) {
            return false;
        }
        if (ConsentPolicy::mayEmail($provider)) {
            return $this->provider($provider, 'job', [], $job, $offer);
        }
        if (! ConsentPolicy::mayEmailRequest($provider)) {
            Log::info('Fachora: klantaanvraag niet gemaild (afgemeld of geblokkeerd)', ['provider' => $provider->id, 'job' => $job->id]);

            return false;
        }

        return $this->send($provider->email, 'pl', new FachoraMail('job', 'provider', $job, $offer, $provider, ['request' => true]));
    }

    /**
     * Aan wie een profiel claimt of een bedrijf aanmeldt: het adres dat die
     * persoon zelf zojuist invulde. Dat is een antwoord op zijn eigen verzoek,
     * geen marketing.
     */
    public function claimant(Provider $provider, string $kind, string $email, array $extra = []): bool
    {
        return $this->send($email, app()->getLocale() === 'en' ? 'en' : 'pl', new FachoraMail($kind, 'provider', null, null, $provider, $extra));
    }

    public function owner(string $kind, array $extra = [], ?ServiceJob $job = null, ?Provider $provider = null): bool
    {
        if ($job?->isDemo() || $provider?->isDemo()) {
            return false; // geen demo-ruis in de inbox van de eigenaar
        }

        $sent = false;
        foreach (OwnerAccess::emails() as $email) {
            $sent = $this->send($email, 'nl', new FachoraMail($kind, 'owner', $job, null, $provider, $extra)) || $sent;
        }

        return $sent;
    }

    public function customerLocale(ServiceJob $job): string
    {
        return $job->customer_locale === 'en' ? 'en' : 'pl';
    }

    public function providerLocale(Provider $provider): string
    {
        return $provider->user?->locale === 'en' ? 'en' : 'pl';
    }

    private function send(?string $to, string $locale, FachoraMail $mail): bool
    {
        if (blank($to)) {
            return false;
        }

        try {
            DocumentLocale::using($locale, fn () => Mail::to($to)->send($mail));

            return true;
        } catch (\Throwable $e) {
            Log::error('Fachora-mail mislukt', ['to' => $to, 'kind' => $mail->kind, 'audience' => $mail->audience, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
