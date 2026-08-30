<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\OnlinePayment;
use App\Models\Payment;
use App\Support\Market;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Betaallink op de factuur (iDEAL, of BLIK / Przelewy24 in Polen) via Mollie. Elke administratie koppelt
 * zijn éigen Mollie-account (Instellingen → Bedrijfsgegevens): het geld gaat
 * dus rechtstreeks naar de rekening van de ondernemer, EasyInvoice zit er
 * niet tussen. Zonder key blijft de betaalknop overal verborgen.
 *
 * Zodra Mollie 'paid' meldt (webhook, of de statuscheck bij terugkeer in het
 * portaal) wordt er een gewone betaling op de factuur geboekt — de bestaande
 * Payment-hooks werken dan het openstaande bedrag en de status bij.
 */
class MolliePaymentService
{
    private const API = 'https://api.mollie.com/v2';

    public function enabledFor(?Company $company): bool
    {
        return filled($company?->mollie_api_key);
    }

    /** Kan er voor deze factuur (nu) online betaald worden? */
    public function payable(Invoice $invoice): bool
    {
        return $this->enabledFor($invoice->company)
            && ! $invoice->is_credit
            && in_array($invoice->status, ['sent', 'partial', 'overdue'], true)
            && $invoice->remaining_amount > 0.009;
    }

    /**
     * Maak (of hervat) een betaling en geef de checkout-URL terug.
     *
     * @throws \DomainException met een nette Nederlandse melding
     */
    public function checkoutUrl(Invoice $invoice): string
    {
        if (! $this->payable($invoice)) {
            throw new \DomainException(__('Deze factuur kan niet (meer) online worden betaald.'));
        }

        $amount = round((float) $invoice->remaining_amount, 2);

        // Een open betaling voor hetzelfde bedrag hervatten we gewoon —
        // dat voorkomt een woud aan losse betaalpogingen bij Mollie.
        $existing = OnlinePayment::where('invoice_id', $invoice->id)
            ->where('status', 'open')
            ->where('amount', number_format($amount, 2, '.', ''))
            ->whereNotNull('checkout_url')
            ->latest('id')
            ->first();
        if ($existing && $this->sync($existing)->isOpen()) {
            return $existing->checkout_url;
        }

        $payload = [
            'amount' => ['currency' => Market::currency(), 'value' => number_format($amount, 2, '.', '')],
            'description' => trim(__('Factuur :number — :company', ['number' => $invoice->number ?: $invoice->id, 'company' => $invoice->company->name])),
            'redirectUrl' => route('portal.invoice', $invoice->portal_token) . '?betaald=1',
            'locale' => Market::isPl() ? 'pl_PL' : 'nl_NL',
            'metadata' => ['invoice_id' => $invoice->id],
        ];

        // Betaalmethode per markt (config/markets.php → payment.mollie_methods):
        // staat er precies één, dan sturen we die mee (bijv. iDEAL); bij
        // meerdere (BLIK / Przelewy24 / kaart) laten we Mollie kiezen.
        $methods = array_values(array_filter((array) Market::get('payment.mollie_methods', [])));
        if (count($methods) === 1) {
            $payload['method'] = $methods[0];
        }

        // Mollie accepteert alleen publiek bereikbare webhook-URL's; lokaal
        // laten we hem weg — de statuscheck bij terugkeer vangt het dan op.
        $webhookUrl = route('mollie.webhook');
        if (! str_contains($webhookUrl, 'localhost') && ! str_contains($webhookUrl, '127.0.0.1')) {
            $payload['webhookUrl'] = $webhookUrl;
        }

        $response = Http::withToken($invoice->company->mollie_api_key)
            ->timeout(15)
            ->post(self::API . '/payments', $payload);

        if ($response->status() === 401) {
            Log::warning('Mollie: ongeldige API-key', ['company' => $invoice->company_id]);
            throw new \DomainException(__('De Mollie-koppeling is verkeerd geconfigureerd. Neem contact op met de afzender van de factuur.'));
        }
        if ($response->failed()) {
            Log::warning('Mollie: betaling aanmaken mislukt', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 300)]);
            throw new \DomainException(__('De betaling kon niet worden gestart. Probeer het zo opnieuw.'));
        }

        $data = $response->json();
        $checkout = $data['_links']['checkout']['href'] ?? null;
        if (! $checkout) {
            throw new \DomainException(__('De betaling kon niet worden gestart. Probeer het zo opnieuw.'));
        }

        OnlinePayment::create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'mollie_id' => $data['id'],
            'checkout_url' => $checkout,
            'amount' => $amount,
            'status' => $data['status'] ?? 'open',
        ]);

        return $checkout;
    }

    /** Haal de actuele status op bij Mollie en boek de betaling zodra die binnen is. */
    public function sync(OnlinePayment $onlinePayment): OnlinePayment
    {
        $company = $onlinePayment->company;
        if (! $this->enabledFor($company)) {
            return $onlinePayment;
        }

        $response = Http::withToken($company->mollie_api_key)
            ->timeout(15)
            ->get(self::API . '/payments/' . $onlinePayment->mollie_id);

        if ($response->failed()) {
            Log::warning('Mollie: status ophalen mislukt', ['mollie_id' => $onlinePayment->mollie_id, 'status' => $response->status()]);

            return $onlinePayment;
        }

        $data = $response->json();
        $onlinePayment->update([
            'status' => $data['status'] ?? $onlinePayment->status,
            'method' => $data['method'] ?? $onlinePayment->method,
            'paid_at' => ! empty($data['paidAt']) ? \Carbon\Carbon::parse($data['paidAt']) : $onlinePayment->paid_at,
        ]);

        if ($onlinePayment->status === 'paid') {
            $this->markPaid($onlinePayment);
        }

        return $onlinePayment->fresh();
    }

    /** Webhook van Mollie: alleen een id — de status halen we zelf veilig op. */
    public function handleWebhook(string $mollieId): void
    {
        $onlinePayment = OnlinePayment::where('mollie_id', $mollieId)->first();
        if ($onlinePayment) {
            $this->sync($onlinePayment);
        }
    }

    /** Boek de betaling op de factuur — idempotent (max. één Payment per betaalpoging). */
    public function markPaid(OnlinePayment $onlinePayment): void
    {
        if ($onlinePayment->payment_id) {
            return; // al geboekt
        }

        $invoice = $onlinePayment->invoice;
        if (! $invoice) {
            return;
        }

        $payment = Payment::create([
            'company_id' => $onlinePayment->company_id,
            'invoice_id' => $invoice->id,
            'kind' => 'payment',
            'amount' => $onlinePayment->amount,
            'paid_on' => ($onlinePayment->paid_at ?? now())->toDateString(),
            'method' => $this->mapMethod($onlinePayment->method),
            'reference' => __('Online betaald via Mollie (:id)', ['id' => $onlinePayment->mollie_id]),
        ]);

        $onlinePayment->update(['payment_id' => $payment->id]);

        // Volledig voldaan? Dan — als de ondernemer dat aan heeft staan — direct
        // een bedankje naar de klant: de betaalbevestiging die hij toch al
        // verwacht na een iDEAL-betaling. Nooit blokkerend voor de webhook.
        app(PaymentThanksService::class)->sendIfEnabled($invoice->fresh());
    }

    protected function mapMethod(?string $mollieMethod): string
    {
        return match ($mollieMethod) {
            'ideal' => 'ideal',
            'blik' => 'blik',
            'przelewy24' => 'p24',
            'creditcard' => 'card',
            'directdebit' => 'direct_debit',
            'banktransfer' => 'bank_transfer',
            default => 'other',
        };
    }
}
