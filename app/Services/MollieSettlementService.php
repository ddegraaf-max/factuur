<?php

namespace App\Services;

use App\Models\Company;
use App\Models\OnlinePayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Welke online betaling zit in welke uitbetaling van Mollie? Mollie maakt het
 * geld niet per betaling over maar gebundeld (een "settlement"), minus zijn
 * kosten. Op het bankafschrift staat één bedrag met een kenmerk als
 * 1234567.2610.03, en de boekhouder wil weten welke facturen daarin zitten.
 *
 * Per betaalde online betaling vragen we Mollie om het settlementId; per
 * uitbetaling halen we kenmerk, datum, status en totaal op. Alles wordt bij
 * de online betaling bewaard, zodat het rapport daarna zonder Mollie werkt.
 */
class MollieSettlementService
{
    private const API = 'https://api.mollie.com/v2';

    /** Hoe ver terug we kijken: na vier maanden is alles allang uitbetaald. */
    private const WINDOW_DAYS = 120;

    /**
     * @return array{checked: int, settled: int, error: ?string}
     */
    public function sync(Company $company, bool $force = false): array
    {
        $result = ['checked' => 0, 'settled' => 0, 'error' => null];
        if (! filled($company->mollie_api_key)) {
            return $result;
        }

        $since = now()->subDays(self::WINDOW_DAYS);

        // Betalingen zonder bekende uitbetaling: vragen bij Mollie of ze
        // inmiddels in een uitbetaling zitten. Niet vaker dan elke zes uur,
        // tenzij de gebruiker er zelf om vraagt.
        $candidates = OnlinePayment::where('company_id', $company->id)
            ->where('status', 'paid')
            ->whereNull('settlement_id')
            ->where('paid_at', '>=', $since)
            ->when(! $force, fn ($q) => $q->where(fn ($q) => $q
                ->whereNull('settlement_checked_at')
                ->orWhere('settlement_checked_at', '<', now()->subHours(6))))
            ->orderBy('paid_at')
            ->limit(200)
            ->get();

        $newSettlements = [];
        foreach ($candidates as $payment) {
            $response = Http::withToken($company->mollie_api_key)
                ->timeout(15)
                ->get(self::API . '/payments/' . $payment->mollie_id);

            if (in_array($response->status(), [401, 403], true)) {
                Log::warning('Mollie: API-key geweigerd bij uitbetalingen', ['company' => $company->id, 'status' => $response->status()]);
                $result['error'] = __('Mollie accepteert de API-key niet (meer). Controleer de key bij Instellingen → Bedrijfsgegevens.');
                break;
            }
            if ($response->failed()) {
                Log::warning('Mollie: betaling ophalen mislukt', ['mollie_id' => $payment->mollie_id, 'status' => $response->status()]);

                continue;
            }

            $data = $response->json();
            $payment->settlement_checked_at = now();
            if (! empty($data['settlementId'])) {
                $payment->settlement_id = (string) $data['settlementId'];
                $newSettlements[$payment->settlement_id] = true;
                $result['settled']++;
            }
            $payment->save();
            $result['checked']++;
        }

        if ($result['error']) {
            return $result;
        }

        // Uitbetalingen die nog niet (als uitbetaald) bekend zijn: kenmerk,
        // datum en totaal ophalen. Een open uitbetaling krijgt pas een datum
        // als Mollie hem heeft overgemaakt, dus die vragen we opnieuw op.
        $pending = OnlinePayment::where('company_id', $company->id)
            ->whereNotNull('settlement_id')
            ->where('paid_at', '>=', $since)
            ->where(fn ($q) => $q->whereNull('settlement_status')->orWhereNotIn('settlement_status', ['paidout', 'failed']))
            ->distinct()
            ->pluck('settlement_id');

        foreach ($pending->merge(array_keys($newSettlements))->unique() as $settlementId) {
            if (! $this->fetchSettlement($company, (string) $settlementId)) {
                break;
            }
        }

        return $result;
    }

    /** Kenmerk, datum, status en totaal van één uitbetaling, bewaard bij alle betalingen erin. */
    public function fetchSettlement(Company $company, string $settlementId): bool
    {
        $response = Http::withToken($company->mollie_api_key)
            ->timeout(15)
            ->get(self::API . '/settlements/' . $settlementId);

        if ($response->failed()) {
            Log::warning('Mollie: uitbetaling ophalen mislukt', ['settlement' => $settlementId, 'status' => $response->status()]);

            return ! in_array($response->status(), [401, 403], true);
        }

        $data = $response->json();
        $date = $data['settledAt'] ?? null;

        OnlinePayment::where('company_id', $company->id)
            ->where('settlement_id', $settlementId)
            ->update([
                'settlement_reference' => isset($data['reference']) ? mb_substr((string) $data['reference'], 0, 64) : null,
                'settlement_status' => isset($data['status']) ? mb_substr((string) $data['status'], 0, 20) : null,
                'settled_on' => $date ? Carbon::parse($date)->toDateString() : null,
                'settlement_total' => isset($data['amount']['value']) ? round((float) $data['amount']['value'], 2) : null,
            ]);

        return true;
    }
}
