<?php

namespace App\Services\Fachora;

use App\Models\ServiceJob;
use App\Models\ServiceJobOffer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Betalingen van Fachora zelf (1.87): het ontgrendelen van een lead door een
 * bedrijf en (als die aan staat) de bijdrage van de klant. Exploitant is
 * Creditline B.V.; de betalingen lopen via het eigen Mollie-account van
 * Creditline (FACHORA_MOLLIE_KEY), in złoty met BLIK, Przelewy24 of kaart.
 * Zonder Mollie-sleutel valt het terug op Stripe (oude configuratie), en
 * zonder beide is alles gratis.
 *
 * Mollie stuurt bij een webhook alleen het id; de status halen we altijd zelf
 * op, dus een vervalste aanroep kan hooguit een statuscontrole uitlokken.
 */
class FachoraPayments
{
    private const API = 'https://api.mollie.com/v2';

    public function __construct(private ProtectService $stripe) {}

    public function mollie(): bool
    {
        return filled(config('fachora.mollie_key'));
    }

    /** Kan er online worden betaald (Mollie of Stripe)? */
    public function configured(): bool
    {
        return $this->mollie() || ((bool) config('fachora.stripe_fallback', false) && $this->stripe->stripeConfigured());
    }

    /* ---------- lead ontgrendelen ---------- */

    public function leadCheckoutUrl(ServiceJobOffer $offer, float $amount, string $returnUrl, string $cancelUrl): string
    {
        if (! $this->mollie()) {
            if (! (bool) config('fachora.stripe_fallback', false)) {
                throw new RuntimeException('Geen betaalpartij ingesteld (FACHORA_MOLLIE_KEY).');
            }

            return $this->stripe->leadCheckoutUrl($offer, $amount, $returnUrl, $cancelUrl);
        }
        $payment = $this->create($amount, 'Fachora ' . $offer->job->reference . ' · odblokowanie zapytania', $returnUrl, [
            'kind' => 'fachora_lead', 'offer_id' => (string) $offer->id,
        ]);
        $offer->forceFill(['lead_session_id' => $payment['id']])->save();

        return $payment['url'];
    }

    public function leadPaid(ServiceJobOffer $offer): bool
    {
        if (blank($offer->lead_session_id)) {
            return false;
        }
        if (! str_starts_with((string) $offer->lead_session_id, 'tr_')) {
            return $this->stripe->leadPaid($offer);
        }

        return $this->paid((string) $offer->lead_session_id, 'fachora_lead', 'offer_id', (string) $offer->id);
    }

    /* ---------- bijdrage van de klant (standaard uit) ---------- */

    public function requestCheckoutUrl(ServiceJob $job, float $amount, string $returnUrl, string $cancelUrl): string
    {
        if (! $this->mollie()) {
            if (! (bool) config('fachora.stripe_fallback', false)) {
                throw new RuntimeException('Geen betaalpartij ingesteld (FACHORA_MOLLIE_KEY).');
            }

            return $this->stripe->requestCheckoutUrl($job, $amount, $returnUrl, $cancelUrl);
        }
        $payment = $this->create($amount, 'Fachora ' . $job->reference . ' · zapytanie', $returnUrl, [
            'kind' => 'fachora_request', 'job_id' => (string) $job->id,
        ]);
        $job->forceFill(['request_session_id' => $payment['id']])->save();

        return $payment['url'];
    }

    public function requestPaid(ServiceJob $job): bool
    {
        if ($job->request_paid_at !== null) {
            return true;
        }
        if (blank($job->request_session_id)) {
            return false;
        }
        if (! str_starts_with((string) $job->request_session_id, 'tr_')) {
            return $this->stripe->requestPaid($job);
        }

        return $this->paid((string) $job->request_session_id, 'fachora_request', 'job_id', (string) $job->id);
    }

    /* ---------- webhook ---------- */

    /** Mollie meldt een statuswijziging: bijwerken wat bij deze betaling hoort. */
    public function handleWebhook(string $id): void
    {
        $payment = $this->fetch($id);
        if ($payment === null || ($payment['status'] ?? null) !== 'paid') {
            return;
        }
        $meta = (array) ($payment['metadata'] ?? []);
        if (($meta['kind'] ?? null) === 'fachora_lead') {
            $offer = ServiceJobOffer::query()->find((int) ($meta['offer_id'] ?? 0));
            if ($offer) {
                if (blank($offer->lead_session_id)) {
                    $offer->forceFill(['lead_session_id' => $id])->save();
                }
                app(LeadService::class)->syncPayment($offer);
            }
        } elseif (($meta['kind'] ?? null) === 'fachora_request') {
            $job = ServiceJob::query()->find((int) ($meta['job_id'] ?? 0));
            if ($job) {
                if (blank($job->request_session_id)) {
                    $job->forceFill(['request_session_id' => $id])->save();
                }
                app(JobService::class)->syncRequestPayment($job);
            }
        }
    }

    /* ---------- Mollie ---------- */

    /** @return array{id: string, url: string} */
    private function create(float $amount, string $description, string $returnUrl, array $metadata): array
    {
        $cents = (int) round($amount * 100);
        if ($cents < 100) {
            throw new RuntimeException('Het bedrag is te laag om online te betalen.');
        }
        $payload = [
            'amount' => ['currency' => 'PLN', 'value' => number_format($cents / 100, 2, '.', '')],
            'description' => mb_substr($description, 0, 255),
            'redirectUrl' => $returnUrl,
            'locale' => 'pl_PL',
            'metadata' => $metadata,
        ];
        // Mollie weigert een webhook-adres dat niet publiek bereikbaar is (lokaal, tests).
        $webhook = route('fachora.mollie.webhook');
        if (str_starts_with($webhook, 'https://')) {
            $payload['webhookUrl'] = $webhook;
        }
        $methods = array_values(array_filter((array) config('fachora.mollie_methods', [])));
        if ($methods !== []) {
            $payload['method'] = $methods;
        }

        $response = Http::withToken((string) config('fachora.mollie_key'))->acceptJson()->asJson()->timeout(20)
            ->post(self::API . '/payments', $payload);
        if (! $response->successful() || blank($response->json('id')) || blank($response->json('_links.checkout.href'))) {
            Log::error('Fachora: Mollie-betaling aanmaken mislukt', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 300)]);
            throw new RuntimeException('Mollie: betaling kon niet worden aangemaakt.');
        }

        return ['id' => (string) $response->json('id'), 'url' => (string) $response->json('_links.checkout.href')];
    }

    /** @return array<string, mixed>|null */
    private function fetch(string $id): ?array
    {
        if (! $this->mollie() || ! preg_match('/^tr_\w+$/', $id)) {
            return null;
        }
        try {
            $response = Http::withToken((string) config('fachora.mollie_key'))->acceptJson()->timeout(15)->get(self::API . '/payments/' . $id);
        } catch (\Throwable $e) {
            Log::warning('Fachora: Mollie-betaling ophalen mislukt', ['id' => $id, 'error' => $e->getMessage()]);

            return null;
        }

        return $response->successful() ? (array) $response->json() : null;
    }

    private function paid(string $id, string $kind, string $key, string $value): bool
    {
        $payment = $this->fetch($id);

        return $payment !== null
            && ($payment['status'] ?? null) === 'paid'
            && (($payment['metadata']['kind'] ?? null) === $kind)
            && (string) ($payment['metadata'][$key] ?? '') === $value;
    }
}
