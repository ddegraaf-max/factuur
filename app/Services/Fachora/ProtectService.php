<?php

namespace App\Services\Fachora;

use App\Models\Provider;
use App\Models\ServiceJob;
use App\Models\ServiceJobOffer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Fachora Protect via Stripe Connect.
 *
 * De klant betaalt aan het platform (Checkout, BLIK/Przelewy24/kaart); het
 * geld staat op het Stripe-saldo van Fachora en gaat pas na akkoord van de
 * klant als transfer naar het Connect-account van het bedrijf, minus de
 * platformfee. Bij een geschil blijft het staan, bij afwijzing gaat het terug.
 * Zo houdt Fachora nooit zelf geld van derden (geen betaalvergunning nodig).
 *
 * Het Connect-account wordt aangemaakt met Stripe-hosted onboarding: Stripe
 * controleert identiteit en bankrekening (KYC) en meldt via account.updated
 * of uitbetalingen aan staan. Dat is tegelijk de bank-/identiteitscheck van
 * Fachora Verified.
 *
 * Zonder STRIPE_SECRET of met FACHORA_PROTECT=false bestaat Protect niet:
 * de klant betaalt dan rechtstreeks aan het bedrijf na afloop.
 */
class ProtectService
{
    private const BASE = 'https://api.stripe.com/v1';

    public function enabled(): bool
    {
        return (bool) config('fachora.protect') && filled(config('services.stripe.secret'));
    }

    /** Kan deze opdracht via Protect worden betaald? */
    public function availableFor(ServiceJob $job): bool
    {
        return $this->enabled() && $job->provider !== null && $job->provider->protectReady();
    }

    /** Het Connect-account van het bedrijf, zo nodig aangemaakt. */
    public function ensureAccount(Provider $provider): string
    {
        if (filled($provider->stripe_account_id)) {
            return $provider->stripe_account_id;
        }

        $account = $this->post('/accounts', array_filter([
            'country' => 'PL',
            'email' => $provider->email,
            'business_type' => $provider->legal_form === 'jdg' ? 'individual' : 'company',
            'business_profile[name]' => $provider->display_name,
            'business_profile[url]' => $provider->website,
            'controller[fees][payer]' => 'application',
            'controller[losses][payments]' => 'application',
            'controller[stripe_dashboard][type]' => 'express',
            'capabilities[transfers][requested]' => 'true',
            'capabilities[card_payments][requested]' => 'true',
            'metadata[provider_id]' => (string) $provider->id,
            'metadata[brand]' => 'fachora',
        ]));

        $provider->forceFill(['stripe_account_id' => $account['id']])->save();

        return $account['id'];
    }

    /** Link naar de Stripe-onboarding (eenmalig geldig, een paar minuten). */
    public function onboardingUrl(Provider $provider, string $returnUrl, string $refreshUrl): string
    {
        $link = $this->post('/account_links', [
            'account' => $this->ensureAccount($provider),
            'return_url' => $returnUrl,
            'refresh_url' => $refreshUrl,
            'type' => 'account_onboarding',
        ]);

        return (string) $link['url'];
    }

    /** Haalt de stand van het account op en zet de vlaggen op het bedrijf. */
    public function syncAccount(Provider $provider): void
    {
        if (blank($provider->stripe_account_id) || ! $this->enabled()) {
            return;
        }
        $this->applyAccount($provider, $this->get('/accounts/' . $provider->stripe_account_id));
    }

    /** @param  array<string, mixed>  $account */
    public function applyAccount(Provider $provider, array $account): void
    {
        $provider->forceFill([
            'stripe_charges_enabled' => (bool) ($account['charges_enabled'] ?? false),
            'stripe_payouts_enabled' => (bool) ($account['payouts_enabled'] ?? false),
            'stripe_requirements' => [
                'currently_due' => $account['requirements']['currently_due'] ?? [],
                'disabled_reason' => $account['requirements']['disabled_reason'] ?? null,
                'details_submitted' => (bool) ($account['details_submitted'] ?? false),
            ],
            'stripe_checked_at' => now(),
        ])->save();
    }

    /** Webhook account.updated: welk bedrijf hoort bij dit account? */
    public function handleAccountUpdated(array $account): void
    {
        $provider = Provider::query()->where('stripe_account_id', $account['id'] ?? '')->first();
        if ($provider) {
            $this->applyAccount($provider, $account);
            app(VerificationService::class)->refreshBankCheck($provider);
        }
    }

    /** Checkout voor de reservering van het afgesproken bedrag. */
    public function checkoutUrl(ServiceJob $job, string $successUrl, string $cancelUrl): string
    {
        if (! $this->availableFor($job)) {
            throw new RuntimeException('Fachora Protect is voor deze opdracht niet beschikbaar.');
        }
        $amount = (int) round((float) $job->agreed_amount * 100);
        if ($amount < 200) {
            throw new RuntimeException('Het bedrag is te laag om online te reserveren.');
        }

        $session = $this->post('/checkout/sessions', [
            'mode' => 'payment',
            'line_items[0][price_data][currency]' => 'pln',
            'line_items[0][price_data][unit_amount]' => $amount,
            'line_items[0][price_data][product_data][name]' => $job->reference . ' · ' . $job->title,
            'line_items[0][price_data][product_data][description]' => 'Fachora Protect: ' . $job->provider->display_name,
            'line_items[0][quantity]' => 1,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $job->customer_email,
            'client_reference_id' => $job->reference,
            'metadata[kind]' => 'fachora_job',
            'metadata[job_id]' => (string) $job->id,
            'payment_intent_data[transfer_group]' => $job->reference,
            'payment_intent_data[metadata][job_id]' => (string) $job->id,
            'payment_intent_data[description]' => 'Fachora ' . $job->reference,
            'locale' => $job->customer_locale === 'en' ? 'en' : 'pl',
        ]);

        $job->forceFill(['stripe_session_id' => $session['id'], 'protect' => true])->save();

        return (string) $session['url'];
    }

    /** Kan er überhaupt via Stripe worden betaald (los van Protect)? */
    public function stripeConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    /**
     * Checkout voor de aanvraagbijdrage van de klant (1.86): een klein vast
     * bedrag in złoty, los van Protect en zonder Connect. BLIK, Przelewy24 en
     * kaart volgen uit de instellingen van het Stripe-account.
     */
    public function requestCheckoutUrl(ServiceJob $job, float $amount, string $successUrl, string $cancelUrl): string
    {
        $cents = (int) round($amount * 100);
        if ($cents < 200) {
            throw new RuntimeException('Het bedrag is te laag om online te betalen.');
        }

        $session = $this->post('/checkout/sessions', [
            'mode' => 'payment',
            'line_items[0][price_data][currency]' => 'pln',
            'line_items[0][price_data][unit_amount]' => $cents,
            'line_items[0][price_data][product_data][name]' => 'Fachora · ' . $job->reference,
            'line_items[0][price_data][product_data][description]' => 'Zapytanie do wybranych firm',
            'line_items[0][quantity]' => 1,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $job->customer_email,
            'client_reference_id' => $job->reference,
            'metadata[kind]' => 'fachora_request',
            'metadata[job_id]' => (string) $job->id,
            'payment_intent_data[description]' => 'Fachora zapytanie ' . $job->reference,
            'locale' => $job->customer_locale === 'en' ? 'en' : 'pl',
        ]);

        $job->forceFill(['request_session_id' => $session['id']])->save();

        return (string) $session['url'];
    }

    /** Is de aanvraagbijdrage van deze opdracht betaald (Checkout-sessie nakijken)? */
    public function requestPaid(ServiceJob $job): bool
    {
        if ($job->request_paid_at !== null) {
            return true;
        }
        if (blank($job->request_session_id)) {
            return false;
        }
        $session = $this->retrieveSession($job->request_session_id);

        return $session !== null
            && ($session['metadata']['kind'] ?? null) === 'fachora_request'
            && (string) ($session['metadata']['job_id'] ?? '') === (string) $job->id
            && ($session['payment_status'] ?? null) === 'paid';
    }

    /**
     * Checkout voor het ontgrendelen van een lead door een bedrijf (1.87).
     * Los van Protect; BLIK, Przelewy24 en kaart volgen uit het Stripe-account.
     */
    public function leadCheckoutUrl(ServiceJobOffer $offer, float $amount, string $successUrl, string $cancelUrl): string
    {
        $cents = (int) round($amount * 100);
        if ($cents < 200) {
            throw new RuntimeException('Het bedrag is te laag om online te betalen.');
        }
        $job = $offer->job;

        $session = $this->post('/checkout/sessions', array_filter([
            'mode' => 'payment',
            'line_items[0][price_data][currency]' => 'pln',
            'line_items[0][price_data][unit_amount]' => $cents,
            'line_items[0][price_data][product_data][name]' => 'Fachora · ' . $job->reference,
            'line_items[0][price_data][product_data][description]' => 'Odblokowanie zapytania klienta',
            'line_items[0][quantity]' => 1,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $offer->provider?->email,
            'client_reference_id' => $job->reference . '-' . $offer->id,
            'metadata[kind]' => 'fachora_lead',
            'metadata[offer_id]' => (string) $offer->id,
            'payment_intent_data[description]' => 'Fachora lead ' . $job->reference,
            'invoice_creation[enabled]' => 'true',
            'tax_id_collection[enabled]' => 'true',
            'locale' => 'pl',
        ], fn ($v) => $v !== null && $v !== ''));

        $offer->forceFill(['lead_session_id' => $session['id']])->save();

        return (string) $session['url'];
    }

    /** Is de lead van deze aanbieding betaald (Checkout-sessie nakijken)? */
    public function leadPaid(ServiceJobOffer $offer): bool
    {
        if (blank($offer->lead_session_id)) {
            return false;
        }
        $session = $this->retrieveSession($offer->lead_session_id);

        return $session !== null
            && ($session['metadata']['kind'] ?? null) === 'fachora_lead'
            && (string) ($session['metadata']['offer_id'] ?? '') === (string) $offer->id
            && ($session['payment_status'] ?? null) === 'paid';
    }

    /** @return array<string, mixed>|null */
    public function retrieveSession(string $sessionId): ?array
    {
        try {
            return $this->get('/checkout/sessions/' . $sessionId);
        } catch (\Throwable $e) {
            Log::warning('Stripe-sessie ophalen mislukt', ['session' => $sessionId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Een afgeronde Checkout (webhook of terugkeerpagina): legt de betaling
     * vast op de opdracht. Geeft de opdracht terug als dit de eerste keer was.
     */
    public function completeSession(array $session): ?ServiceJob
    {
        if (($session['metadata']['kind'] ?? null) !== 'fachora_job' || ($session['payment_status'] ?? null) !== 'paid') {
            return null;
        }
        $job = ServiceJob::query()->find((int) ($session['metadata']['job_id'] ?? 0));
        if (! $job || $job->paid_at !== null) {
            return null;
        }

        $intent = is_array($session['payment_intent'] ?? null) ? $session['payment_intent']['id'] : ($session['payment_intent'] ?? null);
        $charge = null;
        if ($intent) {
            try {
                $charge = $this->get('/payment_intents/' . $intent)['latest_charge'] ?? null;
            } catch (\Throwable $e) {
                Log::warning('Charge van payment intent ophalen mislukt', ['intent' => $intent, 'error' => $e->getMessage()]);
            }
        }

        $job->forceFill([
            'stripe_session_id' => $session['id'] ?? $job->stripe_session_id,
            'stripe_payment_intent' => $intent,
            'stripe_charge_id' => is_array($charge) ? ($charge['id'] ?? null) : $charge,
            'paid_at' => now(),
            'protect' => true,
        ])->save();

        return $job;
    }

    /** Uitbetaling aan het bedrijf (na akkoord van de klant): transfer minus de fee. */
    public function payout(ServiceJob $job): void
    {
        if (! $job->isPaid() || filled($job->stripe_transfer_id) || ! $job->provider?->stripe_account_id) {
            return;
        }
        $amount = (int) round((float) $job->payout_amount * 100);
        if ($amount <= 0) {
            return;
        }

        $transfer = $this->post('/transfers', array_filter([
            'amount' => $amount,
            'currency' => 'pln',
            'destination' => $job->provider->stripe_account_id,
            'transfer_group' => $job->reference,
            'source_transaction' => $job->stripe_charge_id,
            'metadata[job_id]' => (string) $job->id,
            'description' => 'Fachora ' . $job->reference,
        ]));

        $job->forceFill(['stripe_transfer_id' => $transfer['id'], 'payout_at' => now()])->save();
    }

    /** Terugbetaling aan de klant (geheel, of een deel bij een gedeelde uitkomst). */
    public function refund(ServiceJob $job, ?float $amount = null): void
    {
        if (! $job->isPaid() || blank($job->stripe_payment_intent)) {
            return;
        }
        $refund = $this->post('/refunds', array_filter([
            'payment_intent' => $job->stripe_payment_intent,
            'amount' => $amount !== null ? (int) round($amount * 100) : null,
            'metadata[job_id]' => (string) $job->id,
        ]));

        $job->forceFill(['stripe_refund_id' => $refund['id'], 'refunded_at' => $amount === null ? now() : $job->refunded_at])->save();
    }

    /** @return array<string, mixed> */
    private function post(string $path, array $params): array
    {
        $response = Http::withToken((string) config('services.stripe.secret'))->asForm()->timeout(20)->post(self::BASE . $path, $params);
        if (! $response->successful()) {
            $message = (string) ($response->json('error.message') ?? $response->body());
            Log::error('Stripe Connect: aanroep mislukt', ['path' => $path, 'status' => $response->status(), 'error' => mb_substr($message, 0, 300)]);
            throw new RuntimeException('Stripe: ' . mb_substr($message, 0, 200));
        }

        return (array) $response->json();
    }

    /** @return array<string, mixed> */
    private function get(string $path): array
    {
        $response = Http::withToken((string) config('services.stripe.secret'))->timeout(20)->get(self::BASE . $path);
        if (! $response->successful()) {
            throw new RuntimeException('Stripe: ' . mb_substr((string) ($response->json('error.message') ?? $response->body()), 0, 200));
        }

        return (array) $response->json();
    }
}
