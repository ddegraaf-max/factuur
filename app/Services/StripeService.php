<?php

namespace App\Services;

use App\Models\Company;
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
    public function createCheckoutSession(Company $company, string $successUrl, string $cancelUrl): string
    {
        $payload = [
            'mode' => 'subscription',
            'line_items[0][price]' => config('services.stripe.price_id'),
            'line_items[0][quantity]' => 1,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $company->id,
            'allow_promotion_codes' => 'true',
            'subscription_data[metadata][company_id]' => (string) $company->id,
            'metadata[company_id]' => (string) $company->id,
        ];

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

        $periodEnd = $subscription['current_period_end'] ?? null;
        if ($periodEnd) {
            $company->subscription_ends_at = \Illuminate\Support\Carbon::createFromTimestamp($periodEnd);
        }

        $company->save();
    }
}
