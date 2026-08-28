<?php

namespace App\Services\Ponto;

use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\PontoAccount;
use App\Models\PontoConnection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Haalt transacties op voor gekoppelde rekeningen: vraagt Ponto eerst om een
 * verse synchronisatie met de bank en leest dan de transacties (nieuwste
 * eerst) tot een pagina niets nieuws meer bevat. Nieuwe transacties komen als
 * 'open' op Bank & transacties, net als geïmporteerde afschriften.
 */
class PontoSyncer
{
    /** Eerste import: niet verder terug dan dit (anders verdrinkt de open lijst). */
    public const LOOKBACK_DAYS = 90;

    private const PAGE_SIZE = 100;
    private const MAX_PAGES = 20;
    private const POLL_ATTEMPTS = 10;

    public function __construct(private PontoService $ponto)
    {
    }

    /** Synchroniseer alle ingeschakelde rekeningen; geeft het aantal nieuwe transacties terug. */
    public function sync(PontoConnection $connection, bool $refreshAccounts = true): int
    {
        $imported = 0;
        try {
            $token = $this->ponto->accessToken($connection);
            if ($refreshAccounts) {
                $this->ponto->refreshAccounts($connection);
            }
            foreach ($connection->accounts()->where('sync_enabled', true)->get() as $account) {
                try {
                    $this->requestSynchronization($token, $account);
                    $imported += $this->importTransactions($token, $account);
                    $account->forceFill(['last_synced_at' => now(), 'last_error' => null])->save();
                } catch (PontoException $e) {
                    $account->forceFill(['last_error' => $e->getMessage()])->save();
                    Log::warning('Ponto: rekening niet gesynchroniseerd', ['account' => $account->id, 'error' => $e->getMessage()]);
                }
            }
            $connection->forceFill(['last_synced_at' => now(), 'last_error' => null])->save();
        } catch (PontoException $e) {
            $connection->forceFill(['last_error' => $e->getMessage()])->save();
            throw $e;
        }

        return $imported;
    }

    /** Vraag Ponto de rekening bij de bank te verversen en wacht (kort) tot dat klaar is. */
    private function requestSynchronization(string $token, PontoAccount $account): void
    {
        $client = $this->ponto->client();
        try {
            $result = $client->post($token, 'synchronizations', ['data' => ['type' => 'synchronization', 'attributes' => [
                'resourceType' => 'account', 'resourceId' => $account->ponto_id,
                'subtype' => 'accountTransactions', 'customerOnline' => false,
            ]]]);
        } catch (PontoException $e) {
            // Te snel na de vorige (PSD2-limiet) of al bezig: dan lezen we wat Ponto al heeft.
            if (in_array($e->status, [400, 409, 429], true)) {
                return;
            }
            throw $e;
        }

        $id = $result['data']['id'] ?? null;
        $status = $result['data']['attributes']['status'] ?? null;
        for ($i = 0; $id && ! in_array($status, ['success', 'error'], true) && $i < self::POLL_ATTEMPTS; $i++) {
            usleep(1500000);
            $result = $client->get($token, 'synchronizations/' . $id);
            $status = $result['data']['attributes']['status'] ?? null;
        }
        if ($status === 'error') {
            $code = $result['data']['attributes']['errors'][0]['code'] ?? null;
            throw new PontoException('Ponto: de bank kon niet worden bijgewerkt' . ($code ? " ({$code})" : '') . '.', 0, $code);
        }
    }

    private function importTransactions(string $token, PontoAccount $account): int
    {
        $client = $this->ponto->client();
        $since = now()->subDays(self::LOOKBACK_DAYS)->startOfDay();
        $url = "accounts/{$account->ponto_id}/transactions";
        $query = ['page[limit]' => self::PAGE_SIZE];
        $imported = 0;

        for ($page = 0; $url && $page < self::MAX_PAGES; $page++) {
            $result = $client->get($token, $url, $query);
            $query = [];
            $newOnPage = 0;
            $reachedLookback = false;

            foreach ($result['data'] ?? [] as $item) {
                if (empty($item['id'])) {
                    continue;
                }
                $attributes = $this->attributesFor($item, $account);
                if (Carbon::parse($attributes['booking_date'])->lt($since)) {
                    $reachedLookback = true;
                    continue;
                }
                $exists = BankTransaction::withoutGlobalScope('company')
                    ->where('company_id', $account->company_id)
                    ->where('import_hash', $attributes['import_hash'])
                    ->exists();
                if ($exists) {
                    continue;
                }
                BankTransaction::create($attributes);
                $imported++;
                $newOnPage++;
            }

            if ($newOnPage === 0 || $reachedLookback) {
                break;
            }
            $url = $result['links']['next'] ?? null;
        }

        return $imported;
    }

    /** Ponto-transactie → kolommen van bank_transactions (positief = bij, negatief = af). */
    private function attributesFor(array $item, PontoAccount $account): array
    {
        $a = (array) ($item['attributes'] ?? []);
        $date = Carbon::parse($a['executionDate'] ?? $a['valueDate'] ?? $a['createdAt'] ?? 'now');
        $remittance = trim((string) ($a['remittanceInformation'] ?? ''));
        $description = trim((string) ($a['description'] ?? ''));
        $text = $remittance !== '' && $description !== '' && $remittance !== $description
            ? "{$remittance} · {$description}"
            : ($remittance !== '' ? $remittance : $description);
        $iban = isset($a['counterpartReference']) ? strtoupper((string) preg_replace('/\s+/', '', (string) $a['counterpartReference'])) : '';

        return [
            'company_id' => $account->company_id,
            'ponto_account_id' => $account->id,
            'booking_date' => $date->format('Y-m-d'),
            'amount' => round((float) ($a['amount'] ?? 0), 2),
            'currency' => strtoupper((string) ($a['currency'] ?? $account->currency ?? 'EUR')),
            'counterparty_name' => isset($a['counterpartName']) ? mb_substr((string) $a['counterpartName'], 0, 190) : null,
            'counterparty_iban' => $iban !== '' ? mb_substr($iban, 0, 40) : null,
            'description' => $text !== '' ? mb_substr($text, 0, 1000) : null,
            'status' => 'open',
            'source' => 'ponto',
            'import_hash' => 'ponto:' . $item['id'],
        ];
    }

    /** Gegevens voor het koppelingsblok op de bankpagina. */
    public function summary(Company $company, bool $canManage): array
    {
        $connection = $company->pontoConnection;

        return [
            'available' => $this->ponto->available(),
            'sandbox' => $this->ponto->sandbox(),
            'can_manage' => $canManage,
            'connected' => $connection !== null,
            'status' => $connection?->status,
            'last_synced_label' => $connection?->last_synced_at?->translatedFormat('j M Y, H:i'),
            'last_error' => $connection?->last_error,
            'accounts' => $connection ? $connection->accounts()->orderBy('iban')->get()->map(fn (PontoAccount $a) => [
                'id' => $a->id,
                'label' => $a->label(),
                'name' => $a->name,
                'bank_name' => $a->bank_name,
                'currency' => $a->currency,
                'balance' => $a->current_balance !== null ? (float) $a->current_balance : null,
                'sync_enabled' => (bool) $a->sync_enabled,
                'last_synced_label' => $a->last_synced_at?->translatedFormat('j M, H:i'),
                'reauth_label' => $a->authorization_expires_at?->translatedFormat('j M Y'),
                'reauth_soon' => $a->authorization_expires_at !== null && $a->authorization_expires_at->lt(now()->addDays(14)),
                'last_error' => $a->last_error,
            ])->values()->all() : [],
        ];
    }
}
