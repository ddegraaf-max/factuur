<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Peppol: e-facturen rechtstreeks afleveren in het boekhoudpakket van de klant.
 *
 * Twee onderdelen:
 *  1. BEREIKBAARHEIDSCHECK — via de openbare Peppol Directory. Werkt altijd,
 *     zonder account: is deze klant (KvK-nummer) aangesloten op Peppol?
 *  2. VERZENDEN — kan uitsluitend via een gecertificeerd Peppol Access Point.
 *     We gebruiken Storecove als provider (STORECOVE_API_TOKEN +
 *     STORECOVE_LEGAL_ENTITY_ID). Zonder die keys blijft de verzendknop weg.
 */
class PeppolService
{
    private const DIRECTORY = 'https://directory.peppol.eu/search/1.0/json';
    private const STORECOVE = 'https://api.storecove.com/api/v2';

    /** Peppol-deelnemers-ID van de klant: eigen invoer of afgeleid van KvK. */
    public function participantId(Customer $customer): ?string
    {
        if ($customer->peppol_id) {
            $id = trim($customer->peppol_id);

            return str_contains($id, ':') ? $id : '0106:' . preg_replace('/\D/', '', $id);
        }

        $kvk = preg_replace('/\D/', '', (string) $customer->kvk_number);

        return strlen($kvk) === 8 ? "0106:{$kvk}" : null;
    }

    /** Staat dit ID geregistreerd in het Peppol-netwerk? */
    public function isRegistered(string $participantId): bool
    {
        $response = Http::timeout(8)->get(self::DIRECTORY, [
            'participant' => 'iso6523-actorid-upis::' . $participantId,
        ]);

        if ($response->failed()) {
            throw new \DomainException('De Peppol Directory reageert niet. Probeer het later opnieuw.');
        }

        return (int) $response->json('total-result-count', 0) > 0;
    }

    /**
     * Controleer (en cache op de klant, een week geldig) of de klant
     * bereikbaar is via Peppol. Geeft null terug als er geen ID afleidbaar is.
     */
    public function checkCustomer(Customer $customer, bool $force = false): ?bool
    {
        $participantId = $this->participantId($customer);
        if (! $participantId) {
            return null;
        }

        $fresh = $customer->peppol_checked_at
            && $customer->peppol_checked_at->gt(now()->subDays(7));

        if (! $force && $fresh) {
            return (bool) $customer->peppol_available;
        }

        try {
            $available = $this->isRegistered($participantId);
        } catch (\DomainException $e) {
            // Directory tijdelijk onbereikbaar: oude waarde behouden.
            return $customer->peppol_available;
        }

        $customer->forceFill([
            'peppol_available' => $available,
            'peppol_checked_at' => now(),
        ])->saveQuietly();

        return $available;
    }

    /** Is de verzendkant (Storecove) geconfigureerd? */
    public function sendingEnabled(): bool
    {
        return filled(config('services.peppol.storecove_token'))
            && filled(config('services.peppol.legal_entity_id'));
    }

    /**
     * Lever de factuur af via het Peppol-netwerk (UBL/NLCIUS via Storecove).
     * Geeft de referentie (guid) van de verzending terug.
     */
    public function send(Invoice $invoice): string
    {
        if (! $this->sendingEnabled()) {
            throw new \DomainException('Peppol-verzending is nog niet geconfigureerd (STORECOVE_API_TOKEN en STORECOVE_LEGAL_ENTITY_ID).');
        }
        if ($invoice->status === 'draft' || ! $invoice->number) {
            throw new \DomainException('Verstuur de factuur eerst; concepten kunnen niet via Peppol.');
        }

        $customer = $invoice->customer;
        $participantId = $customer ? $this->participantId($customer) : null;
        if (! $participantId) {
            throw new \DomainException('Geen Peppol-ID bekend voor deze klant (KvK-nummer of Peppol-ID invullen).');
        }

        $invoice->load('lines');
        $ubl = app(UblGenerator::class)->generate($invoice);

        [$scheme, $identifier] = explode(':', $participantId, 2);
        $storecoveScheme = match ($scheme) {
            '0106' => 'NL:KVK',
            '0190' => 'NL:OINO',
            '9944' => 'NL:VAT',
            default => 'NL:KVK',
        };

        $response = Http::timeout(20)
            ->withToken(config('services.peppol.storecove_token'))
            ->acceptJson()
            ->post(self::STORECOVE . '/document_submissions', [
                'legalEntityId' => (int) config('services.peppol.legal_entity_id'),
                'routing' => [
                    'eIdentifiers' => [
                        ['scheme' => $storecoveScheme, 'id' => $identifier],
                    ],
                ],
                'document' => [
                    'documentType' => 'invoice',
                    'rawDocumentData' => [
                        'document' => base64_encode($ubl),
                        'parse' => true,
                        'parseStrategy' => 'ubl',
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::warning('Peppol-verzending mislukt', [
                'invoice' => $invoice->id,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            throw new \DomainException(match (true) {
                $response->status() === 401 => 'De Storecove API-token is ongeldig.',
                $response->status() === 422 => 'De factuur is door het Peppol-netwerk geweigerd (validatie). Controleer de bedrijfs- en klantgegevens.',
                default => 'Afleveren via Peppol is niet gelukt. Probeer het later opnieuw.',
            });
        }

        $guid = $response->json('guid') ?? 'onbekend';

        $invoice->forceFill([
            'peppol_sent_at' => now(),
            'peppol_reference' => $guid,
            'peppol_status' => 'sent',
        ])->saveQuietly();

        return $guid;
    }
}
