<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mail vanaf eigen domein via de Resend Domains API: domein aanmelden, de
 * DNS-records (DKIM, SPF/Return-Path, DMARC-advies) tonen, verificatie
 * controleren en weer loskoppelen. Pas bij status 'verified' gebruikt
 * App\Support\Sender het eigen afzenderadres.
 */
class MailDomainService
{
    public function configured(): bool
    {
        return filled(config('services.resend.key'));
    }

    /** Domein aanmelden bij Resend en de DNS-records op de administratie bewaren. */
    public function connect(Company $company, string $domain, string $fromLocalPart): array
    {
        $domain = mb_strtolower(trim($domain));
        $from = mb_strtolower(trim($fromLocalPart)) . '@' . $domain;

        if ($company->mail_domain_id && $company->mail_domain !== $domain) {
            $this->disconnect($company);
        }

        $data = $company->mail_domain_id
            ? $this->json($this->http()->get('/domains/' . $company->mail_domain_id), 'Het domein kon niet worden opgehaald.')
            : $this->json($this->http()->post('/domains', ['name' => $domain, 'region' => 'eu-west-1']), 'Het domein kon niet worden aangemeld.');

        $company->forceFill([
            'mail_domain' => $domain,
            'mail_domain_id' => $data['id'] ?? $company->mail_domain_id,
            'mail_domain_status' => $this->status($data['status'] ?? 'pending'),
            'mail_from_address' => $from,
            'mail_domain_records' => $this->records($data['records'] ?? []),
            'mail_domain_checked_at' => now(),
        ])->save();

        return $company->mail_domain_records;
    }

    /** Verificatie (opnieuw) laten uitvoeren en de status ophalen. */
    public function refresh(Company $company): string
    {
        if (! $company->mail_domain_id) {
            return 'none';
        }
        try {
            $this->http()->post('/domains/' . $company->mail_domain_id . '/verify');
        } catch (\Throwable $e) {
            Log::info('Resend: verify-aanroep mislukt', ['error' => $e->getMessage()]);
        }
        $data = $this->json($this->http()->get('/domains/' . $company->mail_domain_id), 'De domeinstatus kon niet worden opgehaald.');

        $company->forceFill([
            'mail_domain_status' => $this->status($data['status'] ?? 'pending'),
            'mail_domain_records' => $this->records($data['records'] ?? []) ?: $company->mail_domain_records,
            'mail_domain_checked_at' => now(),
        ])->save();

        return $company->mail_domain_status;
    }

    public function disconnect(Company $company): void
    {
        if ($company->mail_domain_id) {
            try {
                $this->http()->delete('/domains/' . $company->mail_domain_id);
            } catch (\Throwable $e) {
                Log::warning('Resend: domein verwijderen mislukt', ['domain' => $company->mail_domain, 'error' => $e->getMessage()]);
            }
        }
        $company->forceFill([
            'mail_domain' => null, 'mail_domain_id' => null, 'mail_domain_status' => null,
            'mail_from_address' => null, 'mail_domain_records' => null, 'mail_domain_checked_at' => null,
        ])->save();
    }

    /** Resend-statussen terugbrengen tot drie: pending, verified, failed. */
    protected function status(string $status): string
    {
        return match ($status) {
            'verified' => 'verified',
            'failed', 'temporary_failure' => 'failed',
            default => 'pending',
        };
    }

    /** @return array<int, array{record: string, type: string, name: string, value: string, priority: ?int, status: string}> */
    protected function records(array $records): array
    {
        return array_values(array_map(fn ($r) => [
            'record' => (string) ($r['record'] ?? ''),
            'type' => (string) ($r['type'] ?? ''),
            'name' => (string) ($r['name'] ?? ''),
            'value' => (string) ($r['value'] ?? ''),
            'priority' => isset($r['priority']) ? (int) $r['priority'] : null,
            'status' => (string) ($r['status'] ?? 'not_started'),
        ], $records));
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl('https://api.resend.com')->withToken((string) config('services.resend.key'))->acceptJson()->timeout(20);
    }

    protected function json($response, string $fallback): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }
        Log::warning('Resend Domains API-fout', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 400)]);
        $detail = $response->json('message');

        throw new \DomainException($fallback . ($detail ? " ({$detail})" : ''));
    }
}
