<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Lichte Stripe-integratie via de HTTP-API (zonder extra Composer-pakket).
 * Verzorgt Checkout (abonnement), het klantportaal en webhook-verificatie.
 */
class StripeService
{
    private const BASE = 'https://api.stripe.com/v1';

    public function configured(): bool
    {
        return ! empty(config('services.stripe.secret'))
            && ! empty(config('services.stripe.price_id'));
    }

    /** Is het Slim-abonnement af te sluiten (aparte Stripe-price ingesteld)? */
    public function slimConfigured(): bool
    {
        return $this->configured() && ! empty(config('services.stripe.price_id_slim'));
    }

    /** Is de toeslag voor de bankkoppeling af te rekenen (aparte Stripe-price per rekening)? */
    public function bankConfigured(): bool
    {
        return $this->configured() && ! empty(config('services.stripe.price_id_bank'));
    }

    /**
     * Zet de hoeveelheid van een toeslag-price op een lopend abonnement: item
     * aanmaken, bijwerken of (bij 0) verwijderen, met verrekening naar rato.
     * Geeft het item-id terug, of null als het item is verwijderd.
     */
    public function setSubscriptionItemQuantity(string $subscriptionId, string $priceId, int $quantity, ?string $itemId = null): ?string
    {
        if ($itemId === null) {
            foreach ($this->retrieveSubscription($subscriptionId)['items']['data'] ?? [] as $item) {
                if (($item['price']['id'] ?? null) === $priceId) {
                    $itemId = $item['id'];
                    break;
                }
            }
        }

        if ($quantity <= 0) {
            if ($itemId) {
                $response = $this->request()->delete(self::BASE.'/subscription_items/'.$itemId, ['proration_behavior' => 'create_prorations']);
                if ($response->failed() && $response->status() !== 404) {
                    throw new RuntimeException('Stripe: toeslag verwijderen mislukt: '.$response->body());
                }
            }

            return null;
        }

        $response = $itemId
            ? $this->request()->post(self::BASE.'/subscription_items/'.$itemId, ['quantity' => $quantity, 'proration_behavior' => 'create_prorations'])
            : $this->request()->post(self::BASE.'/subscription_items', ['subscription' => $subscriptionId, 'price' => $priceId, 'quantity' => $quantity, 'proration_behavior' => 'create_prorations']);
        if ($response->failed()) {
            throw new RuntimeException('Stripe: toeslag bijwerken mislukt: '.$response->body());
        }

        return (string) $response->json('id');
    }

    /**
     * Price-id van de bankkoppeling-toeslag. Mag ook als product-id (prod_…)
     * zijn ingesteld: dan gebruiken we de standaardprice van dat product.
     */
    public function bankPriceId(): ?string
    {
        $configured = (string) config('services.stripe.price_id_bank');
        if ($configured === '') {
            return null;
        }
        if (! str_starts_with($configured, 'prod_')) {
            return $configured;
        }

        return Cache::remember('stripe.bank_price.'.$configured, now()->addDay(), function () use ($configured) {
            $product = $this->request()->get(self::BASE.'/products/'.$configured);
            if ($product->failed()) {
                throw new RuntimeException('Stripe: product voor de bankkoppeling niet gevonden: '.$product->body());
            }
            $price = $product->json('default_price');
            $priceId = is_array($price) ? ($price['id'] ?? null) : $price;
            if (! $priceId) {
                throw new RuntimeException("Stripe: product {$configured} heeft geen standaardprice; stel er een in of gebruik de price-id.");
            }

            return (string) $priceId;
        });
    }

    /** Vertaal een Stripe-price-id naar onze abonnementssmaak. */
    public function planForPrice(?string $priceId): ?string
    {
        return match (true) {
            $priceId !== null && $priceId === config('services.stripe.price_id_slim') => 'slim',
            $priceId !== null && $priceId === config('services.stripe.price_id') => 'basis',
            default => null,
        };
    }

    private function secret(): string
    {
        $secret = config('services.stripe.secret');
        if (empty($secret)) {
            throw new RuntimeException('Stripe is niet geconfigureerd (STRIPE_SECRET ontbreekt).');
        }

        return $secret;
    }

    private function request()
    {
        return Http::withToken($this->secret())->asForm();
    }

    /**
     * Maak een Checkout-sessie (abonnement) en geef de hosted checkout-URL terug.
     */
    public function createCheckoutSession(Company $company, string $successUrl, string $cancelUrl, ?int $trialEnd = null, string $plan = 'basis'): string
    {
        $priceId = $plan === 'slim'
            ? config('services.stripe.price_id_slim')
            : config('services.stripe.price_id');
        if (empty($priceId)) {
            throw new RuntimeException("Stripe-price voor abonnement '{$plan}' is niet geconfigureerd.");
        }

        $payload = [
            'mode' => 'subscription',
            'line_items[0][price]' => $priceId,
            'line_items[0][quantity]' => 1,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $company->id,
            'allow_promotion_codes' => 'true',
            'subscription_data[metadata][company_id]' => (string) $company->id,
            'metadata[company_id]' => (string) $company->id,
        ];

        // Bij afsluiten tijdens de proefperiode: eerste afschrijving pas op het
        // einde van de proef (geen dubbele dagen, geen directe kosten).
        if ($trialEnd) {
            $payload['subscription_data[trial_end]'] = $trialEnd;
        }

        if ($company->stripe_customer_id) {
            $payload['customer'] = $company->stripe_customer_id;
        } elseif ($company->email) {
            $payload['customer_email'] = $company->email;
        }

        $response = $this->request()->post(self::BASE.'/checkout/sessions', $payload);

        if ($response->failed()) {
            throw new RuntimeException('Stripe checkout mislukt: '.$response->body());
        }

        return $response->json('url');
    }

    /**
     * Maak een sessie voor het Stripe-klantportaal (abonnement beheren/opzeggen).
     */
    public function createPortalSession(Company $company, string $returnUrl): string
    {
        if (! $company->stripe_customer_id) {
            throw new RuntimeException('Geen Stripe-klant bekend voor dit bedrijf.');
        }

        $response = $this->request()->post(self::BASE.'/billing_portal/sessions', [
            'customer' => $company->stripe_customer_id,
            'return_url' => $returnUrl,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Stripe portaal mislukt: '.$response->body());
        }

        return $response->json('url');
    }

    public function retrieveSession(string $sessionId): ?array
    {
        $response = $this->request()->get(self::BASE.'/checkout/sessions/'.$sessionId);

        return $response->successful() ? $response->json() : null;
    }

    public function retrieveSubscription(string $subscriptionId): ?array
    {
        $response = $this->request()->get(self::BASE.'/subscriptions/'.$subscriptionId);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Verifieer de handtekening van een inkomende webhook (Stripe-Signature).
     */
    public function verifyWebhookSignature(string $payload, ?string $signatureHeader, int $tolerance = 300): bool
    {
        $secret = config('services.stripe.webhook_secret');
        if (empty($secret) || empty($signatureHeader)) {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 'v1' && $value !== null) {
                $signatures[] = $value;
            }
        }

        if (! $timestamp || empty($signatures)) {
            return false;
        }

        // Bescherm tegen replay-aanvallen.
        if (abs(now()->timestamp - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Werk een bedrijf bij op basis van een Stripe-subscription-object.
     */
    public function applySubscriptionToCompany(Company $company, array $subscription): void
    {
        $company->stripe_subscription_id = $subscription['id'] ?? $company->stripe_subscription_id;

        if (! empty($subscription['customer'])) {
            $company->stripe_customer_id = $subscription['customer'];
        }

        $company->subscription_status = $subscription['status'] ?? $company->subscription_status;

        // Smaak (basis/slim) afleiden uit de afgesloten Stripe-price. Zo klopt
        // het plan ook na een overstap via het Stripe-klantportaal.
        // De bankkoppeling-toeslag is een extra item; zoek het item met een abonnementsprice.
        foreach ($subscription['items']['data'] ?? [] as $item) {
            $plan = $this->planForPrice($item['price']['id'] ?? null);
            if ($plan !== null) {
                $company->plan = $plan;
                break;
            }
        }

        // In nieuwere Stripe-API-versies staat current_period_end op de
        // subscription items i.p.v. op het hoofdobject. Val terug op trial_end.
        $periodEnd = $subscription['items']['data'][0]['current_period_end']
            ?? $subscription['current_period_end']
            ?? $subscription['trial_end']
            ?? null;

        if ($periodEnd) {
            $company->subscription_ends_at = \Illuminate\Support\Carbon::createFromTimestamp($periodEnd);
        }

        $company->save();
    }
}
