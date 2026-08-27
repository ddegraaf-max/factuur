<?php

namespace App\Services\Peppol;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Dunne client voor de Recommand Peppol API (app.recommand.eu/api/v1).
 * Basic-auth met de teamkey van EasyInvoice; alle antwoorden zijn JSON met
 * een top-level "success". Fouten worden vertaald naar nette Nederlandse
 * meldingen (DomainException) zodat controllers ze direct kunnen tonen.
 */
class RecommandClient
{
    public function configured(): bool
    {
        return filled(config('services.peppol.recommand_key')) && filled(config('services.peppol.recommand_secret'));
    }

    /** Bedrijf (administratie) registreren als Peppol-deelnemer. */
    public function createCompany(array $payload): array
    {
        return $this->json($this->http()->post('/companies', $payload), 'Registreren bij het Peppol-netwerk is niet gelukt.');
    }

    public function getCompany(string $companyId): array
    {
        return $this->json($this->http()->get("/companies/{$companyId}"), 'De Peppol-status kon niet worden opgehaald.');
    }

    public function deleteCompany(string $companyId): void
    {
        $this->json($this->http()->delete("/companies/{$companyId}"), 'Afmelden bij het Peppol-netwerk is niet gelukt.');
    }

    /** Is dit Peppol-adres (scheme:id) geregistreerd, en welke documenttypen accepteert het? */
    public function verify(string $peppolAddress): array
    {
        return $this->json($this->http()->post('/verify', [
            'peppolAddress' => $peppolAddress,
            'includeEndpointDetails' => false,
            'includeBusinessCard' => true,
        ]), 'De Peppol-bereikbaarheid kon niet worden gecontroleerd.');
    }

    /** Kant-en-klare UBL-XML afleveren namens een geregistreerd bedrijf. */
    public function sendXml(string $companyId, string $recipient, string $xml, ?string $doctypeId = null, ?string $processId = null): array
    {
        $payload = array_filter([
            'recipient' => $recipient,
            'documentType' => 'xml',
            'document' => $xml,
            'doctypeId' => $doctypeId,
            'processId' => $processId,
        ], fn ($v) => $v !== null);

        return $this->json($this->http()->timeout(30)->post("/{$companyId}/send", $payload), 'Afleveren via Peppol is niet gelukt.');
    }

    public function getDocument(string $documentId): array
    {
        $data = $this->json($this->http()->get("/documents/{$documentId}"), 'Het ontvangen Peppol-document kon niet worden opgehaald.');

        return $data['document'] ?? $data;
    }

    public function getDocumentXml(string $documentId): ?string
    {
        $response = $this->http()->withHeaders(['Accept' => 'application/xml'])->get("/documents/{$documentId}");

        return $response->successful() ? $response->body() : null;
    }

    /** PDF-weergave van een ontvangen document (voor het Postvak IN). */
    public function renderPdf(string $documentId): ?string
    {
        $response = $this->http()->timeout(30)->withHeaders(['Accept' => 'application/pdf'])->get("/documents/{$documentId}/render/pdf");

        return $response->successful() && str_starts_with($response->body(), '%PDF') ? $response->body() : null;
    }

    public function markAsRead(string $documentId): void
    {
        try {
            $this->http()->post("/documents/{$documentId}/mark-as-read");
        } catch (\Throwable $e) {
            Log::info('Recommand: markeren als gelezen mislukt', ['document' => $documentId, 'error' => $e->getMessage()]);
        }
    }

    public function createWebhook(string $url, ?string $secret = null): array
    {
        return $this->json($this->http()->post('/webhooks', array_filter([
            'url' => $url,
            'companyId' => null,
            'secret' => $secret,
        ], fn ($v) => $v !== null)), 'De webhook kon niet worden aangemaakt.');
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.peppol.recommand_base'), '/'))
            ->withBasicAuth((string) config('services.peppol.recommand_key'), (string) config('services.peppol.recommand_secret'))
            ->acceptJson()
            ->timeout(20);
    }

    /** JSON uitpakken; alles wat geen success=true is, wordt een DomainException. */
    protected function json(Response $response, string $fallback): array
    {
        $data = $response->json() ?? [];

        if ($response->successful() && ($data['success'] ?? true) !== false) {
            return $data;
        }

        Log::warning('Recommand API-fout', [
            'status' => $response->status(),
            'body' => mb_substr($response->body(), 0, 600),
        ]);

        $detail = $this->errorText($data);

        throw new \DomainException(match (true) {
            $response->status() === 401 => 'De Recommand API-sleutel is ongeldig of verlopen.',
            $response->status() === 402 => 'Het Recommand-abonnement van EasyInvoice is niet actief.',
            $response->status() === 422 => 'Het Peppol-netwerk heeft het document geweigerd' . ($detail ? ": {$detail}" : '.'),
            $detail !== null => $fallback . ' ' . $detail,
            default => $fallback,
        });
    }

    protected function errorText(array $data): ?string
    {
        foreach (['error', 'message', 'errors'] as $key) {
            if (! empty($data[$key])) {
                $value = $data[$key];
                if (is_array($value)) {
                    $value = implode('; ', array_map(fn ($v) => is_array($v) ? implode(', ', array_map('strval', array_values($v))) : (string) $v, $value));
                }

                return mb_substr(trim((string) $value), 0, 300) ?: null;
            }
        }

        return null;
    }
}
