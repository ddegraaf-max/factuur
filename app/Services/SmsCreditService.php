<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SmsCreditEntry;
use App\Models\SmsMessage;
use App\Models\SmsPurchase;
use Illuminate\Support\Facades\DB;

/**
 * Het sms-tegoed van een administratie: bundels kopen, het tegoed bijhouden en
 * per verstuurde sms afboeken. Betalen loopt via Stripe (eenmalige betaling);
 * het tegoed komt er pas bij als Stripe de betaling bevestigt.
 */
class SmsCreditService
{
    public function __construct(private StripeService $stripe) {}

    /**
     * De bundels met hun prijs.
     *
     * @return array<int, array{credits: int, per_sms: float, price_excl: float, vat: float, price_incl: float}>
     */
    public function bundles(): array
    {
        $markup = (float) config('sms.markup');
        $rate = (float) config('sms.vat_rate');

        return collect(config('sms.bundles', []))
            ->map(function ($cost, $credits) use ($markup, $rate) {
                $perSms = round((float) $cost + $markup, 3);
                $excl = round((int) $credits * $perSms, 2);
                $vat = round($excl * $rate / 100, 2);

                return [
                    'credits' => (int) $credits,
                    'per_sms' => $perSms,
                    'price_excl' => $excl,
                    'vat' => $vat,
                    'price_incl' => round($excl + $vat, 2),
                ];
            })
            ->sortBy('credits')
            ->values()
            ->all();
    }

    /** @return array{credits: int, per_sms: float, price_excl: float, vat: float, price_incl: float}|null */
    public function bundle(int $credits): ?array
    {
        return collect($this->bundles())->firstWhere('credits', $credits);
    }

    /** Hoeveel sms'en deze administratie nog kan versturen. */
    public function balance(Company $company): int
    {
        return (int) SmsCreditEntry::where('company_id', $company->id)->sum('amount');
    }

    /** Boekt een verstuurde sms af van het tegoed. */
    public function spend(Company $company, SmsMessage $message): void
    {
        SmsCreditEntry::create([
            'company_id' => $company->id,
            'amount' => -1 * max(1, (int) $message->segments),
            'kind' => 'use',
            'sms_message_id' => $message->id,
        ]);
    }

    /** Tegoed erbij zonder aankoop, bijvoorbeeld om het uit te proberen. */
    public function gift(Company $company, int $credits, ?string $note = null): void
    {
        SmsCreditEntry::create(['company_id' => $company->id, 'amount' => $credits, 'kind' => 'gift', 'note' => $note]);
    }

    /**
     * Begint een aankoop en geeft het adres van de betaalpagina terug.
     *
     * @throws \DomainException als de bundel niet bestaat of betalen niet kan
     */
    public function checkout(Company $company, int $credits, ?int $userId, string $successUrl, string $cancelUrl): string
    {
        $bundle = $this->bundle($credits);
        if (! $bundle) {
            throw new \DomainException(__('Kies een van de bundels.'));
        }
        if (! $this->stripe->canCharge()) {
            throw new \DomainException(__('Betalen is op dit moment niet mogelijk. Probeer het later opnieuw.'));
        }

        $purchase = SmsPurchase::create([
            'company_id' => $company->id,
            'user_id' => $userId,
            'credits' => $bundle['credits'],
            'price_excl' => $bundle['price_excl'],
            'vat_rate' => (float) config('sms.vat_rate'),
            'price_incl' => $bundle['price_incl'],
            'status' => 'pending',
        ]);

        try {
            $session = $this->stripe->createPaymentSession(
                $company,
                __('Sms-tegoed: :n sms\'en', ['n' => $bundle['credits']]),
                __(':excl exclusief btw', ['excl' => money($bundle['price_excl'])]),
                // Exclusief: Stripe rekent de btw er zelf bij en zet die op de factuur.
                (int) round($bundle['price_excl'] * 100),
                ['kind' => 'sms_credits', 'sms_purchase_id' => (string) $purchase->id, 'company_id' => (string) $company->id],
                $successUrl,
                $cancelUrl,
            );
        } catch (\Throwable $e) {
            $purchase->delete();

            throw new \DomainException(__('Betalen is op dit moment niet mogelijk. Probeer het later opnieuw.'));
        }

        $purchase->forceFill(['stripe_session_id' => $session['id']])->save();

        return $session['url'];
    }

    /**
     * Verwerkt een betaalde sessie van Stripe: het tegoed komt erbij, één keer.
     * Wordt aangeroepen door de webhook én bij terugkeer van de betaalpagina;
     * wie het eerst is, boekt.
     *
     * @param  array<string, mixed>  $session
     */
    public function fulfil(array $session): ?SmsPurchase
    {
        if (($session['metadata']['kind'] ?? null) !== 'sms_credits' || ($session['payment_status'] ?? null) !== 'paid') {
            return null;
        }

        return DB::transaction(function () use ($session) {
            $purchase = SmsPurchase::where('id', (int) ($session['metadata']['sms_purchase_id'] ?? 0))
                ->where('stripe_session_id', $session['id'] ?? '')
                ->lockForUpdate()
                ->first();
            if (! $purchase || $purchase->isPaid()) {
                return $purchase;
            }

            $purchase->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
            SmsCreditEntry::create([
                'company_id' => $purchase->company_id,
                'amount' => $purchase->credits,
                'kind' => 'purchase',
                'sms_purchase_id' => $purchase->id,
            ]);

            return $purchase;
        });
    }

    /** Bij terugkeer van de betaalpagina: de sessie ophalen en verwerken. */
    public function fulfilSession(Company $company, string $sessionId): ?SmsPurchase
    {
        $known = SmsPurchase::where('company_id', $company->id)->where('stripe_session_id', $sessionId)->first();
        if (! $known) {
            return null;
        }
        if ($known->isPaid()) {
            return $known;
        }
        $session = $this->stripe->retrieveSession($sessionId);

        return $session ? $this->fulfil($session) : null;
    }
}
