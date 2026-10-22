<?php

namespace App\Services\Fachora;

use App\Models\ServiceJobOffer;
use App\Support\Fachora;

/**
 * Betalen per lead (1.87). De klant vraagt gratis aan; een bedrijf dat wil
 * reageren, ontgrendelt de aanvraag eerst. De prijs volgt de grootte van de
 * klus (een percentage van de geschatte prijs, tussen een minimum en een
 * maximum), en de eerste lead(s) per bedrijf zijn gratis. Een ontgrendelde
 * lead blijft ontgrendeld: prijs bijwerken of afzeggen kost niets extra.
 */
class LeadService
{
    public function __construct(private FachoraPayments $payments) {}

    public function enabled(): bool
    {
        return (bool) config('fachora.lead_pricing', true);
    }

    /** De volle prijs van deze lead in złoty, los van gratis leads. */
    public function price(ServiceJobOffer $offer): float
    {
        $job = $offer->job;
        $low = (float) ($job->estimate_low ?? 0);
        $high = (float) ($job->estimate_high ?? 0);
        $mid = $low > 0 && $high > 0 ? ($low + $high) / 2 : max($low, $high);
        $min = (float) config('fachora.lead_min', 15);
        $max = (float) config('fachora.lead_max', 150);
        if ($mid <= 0) {
            return round(min($max, max($min, (float) config('fachora.lead_default', 30))));
        }

        return round(min($max, max($min, $mid * (float) config('fachora.lead_percent', 5) / 100)));
    }

    /** Krijgt dit bedrijf deze lead gratis (welkomstlead)? */
    public function isFree(ServiceJobOffer $offer): bool
    {
        $used = ServiceJobOffer::query()
            ->where('provider_id', $offer->provider_id)
            ->whereKeyNot($offer->id)
            ->where('lead_free', true)
            ->whereNotNull('unlocked_at')
            ->count();

        return $used < max(0, (int) config('fachora.lead_free_count', 1));
    }

    /** Wat het bedrijf voor deze lead betaalt (0 = gratis). */
    public function feeFor(ServiceJobOffer $offer): float
    {
        if (! $this->enabled() || ! $this->payments->configured() || $offer->job?->isDemo() || $this->isFree($offer)) {
            return 0.0;
        }

        return $this->price($offer);
    }

    /** Voor de aanbiedingspagina. @return array<string, mixed> */
    public function summary(ServiceJobOffer $offer): array
    {
        $fee = $offer->isUnlocked() ? (float) $offer->lead_fee : $this->feeFor($offer);

        return [
            'unlocked' => $offer->isUnlocked() || ! $this->enabled(),
            'fee' => $fee,
            'fee_label' => Fachora::money($fee),
            'free' => $offer->isUnlocked() ? (bool) $offer->lead_free : ($this->enabled() && $fee <= 0 && $this->isFree($offer)),
            'price' => $this->price($offer),
            'price_label' => Fachora::money($this->price($offer)),
            'free_count' => (int) config('fachora.lead_free_count', 1),
            'min' => (float) config('fachora.lead_min', 15),
            'max' => (float) config('fachora.lead_max', 150),
        ];
    }

    /**
     * Ontgrendelen: gratis meteen, anders het Checkout-adres.
     *
     * @return string|null  het betaaladres, of null als de lead nu ontgrendeld is
     */
    public function unlock(ServiceJobOffer $offer): ?string
    {
        if ($offer->isUnlocked() || ! $this->enabled()) {
            return null;
        }
        if (! $offer->isOpen()) {
            throw new \DomainException(__('Deze aanbieding is niet meer open.'));
        }
        // Al betaald (ander tabblad, webhook nog onderweg)? Dan niet opnieuw laten betalen.
        if ($this->syncPayment($offer)) {
            return null;
        }

        $fee = $this->feeFor($offer);
        if ($fee <= 0) {
            $this->markUnlocked($offer, 0.0, $this->enabled() && $this->isFree($offer));

            return null;
        }

        return $this->payments->leadCheckoutUrl($offer, $fee, route('fachora.offer.unlocked', $offer->token), $offer->url());
    }

    /** Terug van Stripe of via de webhook: betaald? Dan ontgrendeld. */
    public function syncPayment(ServiceJobOffer $offer): bool
    {
        if ($offer->isUnlocked()) {
            return true;
        }
        if (blank($offer->lead_session_id) || ! $this->payments->leadPaid($offer)) {
            return false;
        }
        $this->markUnlocked($offer, $this->price($offer), false);

        return true;
    }

    /** Voor respond(): mag dit bedrijf nu een prijs geven? Gratis leads gaan vanzelf open. */
    public function ensureUnlocked(ServiceJobOffer $offer): void
    {
        if ($offer->isUnlocked() || ! $this->enabled()) {
            return;
        }
        if ($this->feeFor($offer) <= 0) {
            $this->markUnlocked($offer, 0.0, $this->isFree($offer));

            return;
        }

        throw new \DomainException(__('Ontgrendel eerst deze aanvraag (:amount); daarna kunt u een prijs geven.', ['amount' => Fachora::money($this->price($offer))]));
    }

    private function markUnlocked(ServiceJobOffer $offer, float $fee, bool $free): void
    {
        $marked = ServiceJobOffer::query()->whereKey($offer->id)->whereNull('unlocked_at')
            ->update(['unlocked_at' => now(), 'lead_fee' => $fee, 'lead_free' => $free]) === 1;
        $offer->refresh();
        if ($marked) {
            app(JobService::class)->log($offer->job, 'lead_unlocked', 'provider', $free
                ? __(':provider ontgrendelde de aanvraag (gratis welkomstlead)', ['provider' => $offer->provider->display_name])
                : __(':provider ontgrendelde de aanvraag: :amount', ['provider' => $offer->provider->display_name, 'amount' => Fachora::money($fee)]));
        }
    }
}
