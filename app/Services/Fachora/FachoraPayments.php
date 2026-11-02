<?php

namespace App\Services\Fachora;

use App\Models\ServiceJob;
use App\Models\ServiceJobOffer;

/**
 * Betalingen van Fachora zelf (1.87): het ontgrendelen van een lead door een
 * bedrijf en (als die aan staat) de bijdrage van de klant. Exploitant is
 * Creditline B.V.; alles loopt via het Stripe-account van Creditline
 * (STRIPE_SECRET) met Stripe Checkout in złoty: BLIK, Przelewy24 of kaart.
 * Zonder Stripe-sleutel is alles gratis.
 *
 * Tot 1.91 kon dit ook via een Mollie-account (FACHORA_MOLLIE_KEY, webhook
 * /webhooks/mollie-fachora); die tweede weg is weg: één betaalpartij.
 * De status wordt bij terugkeer rechtstreeks bij Stripe opgehaald, dus de
 * leadbetaling hangt niet aan een webhook.
 */
class FachoraPayments
{
    public function __construct(private ProtectService $stripe) {}

    /** Kan er online worden betaald? */
    public function configured(): bool
    {
        return $this->stripe->stripeConfigured();
    }

    /* ---------- lead ontgrendelen ---------- */

    public function leadCheckoutUrl(ServiceJobOffer $offer, float $amount, string $returnUrl, string $cancelUrl): string
    {
        return $this->stripe->leadCheckoutUrl($offer, $amount, $returnUrl, $cancelUrl);
    }

    public function leadPaid(ServiceJobOffer $offer): bool
    {
        return filled($offer->lead_session_id) && $this->stripe->leadPaid($offer);
    }

    /* ---------- bijdrage van de klant (standaard uit) ---------- */

    public function requestCheckoutUrl(ServiceJob $job, float $amount, string $returnUrl, string $cancelUrl): string
    {
        return $this->stripe->requestCheckoutUrl($job, $amount, $returnUrl, $cancelUrl);
    }

    public function requestPaid(ServiceJob $job): bool
    {
        if ($job->request_paid_at !== null) {
            return true;
        }

        return filled($job->request_session_id) && $this->stripe->requestPaid($job);
    }
}
