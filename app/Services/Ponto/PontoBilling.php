<?php

namespace App\Services\Ponto;

use App\Models\Company;
use App\Models\PontoConnection;
use App\Services\StripeService;
use App\Support\ErrorAlert;
use Illuminate\Support\Facades\Log;

/**
 * De bankkoppeling wordt per gesynchroniseerde rekening afgerekend als extra
 * regel op het Stripe-abonnement (hoeveelheid = aantal rekeningen). Vrijgestelde
 * administraties betalen niets; zonder betaald abonnement is koppelen niet mogelijk.
 */
class PontoBilling
{
    public function __construct(private StripeService $stripe)
    {
    }

    public function pricePerAccount(): float
    {
        return (float) config('services.ponto.account_price', 5);
    }

    public function priceLabel(): string
    {
        return '€ ' . number_format($this->pricePerAccount(), 2, ',', '.') . ' per rekening per maand, excl. btw';
    }

    public function monthlyCostLabel(int $quantity): string
    {
        return sprintf('%d rekening%s · € %s per maand, excl. btw', $quantity, $quantity === 1 ? '' : 'en', number_format($quantity * $this->pricePerAccount(), 2, ',', '.'));
    }

    public function canConnect(Company $company): bool
    {
        return $this->connectBlocker($company) === null;
    }

    /** Reden waarom koppelen (nog) niet kan, of null als het mag. */
    public function connectBlocker(Company $company): ?string
    {
        if ($company->is_exempt) {
            return null;
        }
        if (! $company->subscriptionActive() || ! $company->stripe_subscription_id) {
            return 'De bankkoppeling is beschikbaar zodra je een abonnement hebt afgesloten (' . $this->priceLabel() . ').';
        }
        if (! $this->stripe->bankConfigured()) {
            return 'De bankkoppeling is nog niet af te rekenen; probeer het binnenkort opnieuw.';
        }

        return null;
    }

    /** Aantal rekeningen dat wordt afgerekend: de ingeschakelde rekeningen van de koppeling. */
    public function billableQuantity(PontoConnection $connection): int
    {
        return $connection->accounts()->where('sync_enabled', true)->count();
    }

    /** Stripe-hoeveelheid gelijktrekken met het aantal rekeningen (alleen als er iets verandert). */
    public function syncQuantity(PontoConnection $connection, ?int $quantity = null): void
    {
        $quantity ??= $this->billableQuantity($connection);
        $company = $connection->company;

        if (! $company || $company->is_exempt || ! $company->stripe_subscription_id || ! $this->stripe->bankConfigured()) {
            $connection->forceFill(['billed_quantity' => $quantity])->save();

            return;
        }
        if ($quantity === (int) $connection->billed_quantity && ($quantity === 0 || $connection->stripe_item_id)) {
            return;
        }

        try {
            $itemId = $this->stripe->setSubscriptionItemQuantity(
                (string) $company->stripe_subscription_id,
                (string) config('services.stripe.price_id_bank'),
                $quantity,
                $connection->stripe_item_id,
            );
            $connection->forceFill(['stripe_item_id' => $itemId, 'billed_quantity' => $quantity])->save();
        } catch (\Throwable $e) {
            Log::error('Ponto: toeslag in Stripe bijwerken mislukt', ['company' => $company->id, 'quantity' => $quantity, 'error' => $e->getMessage()]);
            ErrorAlert::report(new \RuntimeException("Bankkoppeling-toeslag niet bijgewerkt voor administratie {$company->id} ({$quantity} rekening(en)): " . $e->getMessage(), 0, $e));
        }
    }
}
