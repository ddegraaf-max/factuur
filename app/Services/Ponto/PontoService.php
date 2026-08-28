<?php

namespace App\Services\Ponto;

use App\Models\Company;
use App\Models\PontoAccount;
use App\Models\PontoConnection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Bankkoppeling via Ponto Connect: OAuth2-autorisatie (met PKCE), tokens
 * verversen, rekeningen bijhouden en de koppeling verbreken.
 * Transacties ophalen zit in PontoSyncer.
 */
class PontoService
{
    public const SESSION_KEY = 'ponto.oauth';

    private ?PontoClient $client;

    public function __construct()
    {
        $this->client = PontoClient::fromConfig();
    }

    public function available(): bool { return $this->client !== null; }
    public function sandbox(): bool { return $this->client?->sandbox() ?? false; }

    public function client(): PontoClient
    {
        if (! $this->client) {
            throw new PontoException('De bankkoppeling is niet ingericht (PONTO_* ontbreekt).');
        }

        return $this->client;
    }

    /** Stap 1: URL waar de gebruiker bij Ponto toestemming geeft; state en PKCE-verifier gaan in de sessie. */
    public function authorizationUrl(string $redirectUri): string
    {
        $client = $this->client();
        $verifier = self::base64url(random_bytes(48));
        $state = bin2hex(random_bytes(16));
        session([self::SESSION_KEY => ['verifier' => $verifier, 'state' => $state, 'redirect_uri' => $redirectUri]]);

        return $client->authorizationUrl() . '?' . http_build_query([
            'client_id' => $client->clientId(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'ai offline_access',
            'state' => $state,
            'code_challenge' => self::base64url(hash('sha256', $verifier, true)),
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** Stap 2: terug van Ponto — code inwisselen voor tokens en de rekeningen ophalen. */
    public function completeAuthorization(Company $company, string $code, string $state): PontoConnection
    {
        $pending = session(self::SESSION_KEY);
        session()->forget(self::SESSION_KEY);
        if (! is_array($pending) || $code === '' || ! hash_equals((string) ($pending['state'] ?? ''), $state)) {
            throw new PontoException('De koppeling kon niet worden geverifieerd. Start het koppelen opnieuw.');
        }

        $token = $this->client()->exchangeCode($code, (string) $pending['redirect_uri'], (string) $pending['verifier']);

        $connection = PontoConnection::updateOrCreate(['company_id' => $company->id], [
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'] ?? null,
            'token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 1800)),
            'scope' => $token['scope'] ?? null,
            'status' => PontoConnection::STATUS_ACTIVE,
            'sandbox' => $this->sandbox(),
            'connected_at' => now(),
            'last_error' => null,
        ]);

        $this->refreshAccounts($connection);

        return $connection;
    }

    /** Geldig accesstoken; ververst via het refresh-token als het (bijna) verlopen is. */
    public function accessToken(PontoConnection $connection): string
    {
        if ($connection->access_token && $connection->token_expires_at?->isAfter(now()->addMinutes(2))) {
            return $connection->access_token;
        }
        if (! $connection->refresh_token) {
            $connection->forceFill(['status' => PontoConnection::STATUS_NEEDS_REAUTH])->save();
            throw new PontoException('De bankkoppeling moet opnieuw worden geautoriseerd.');
        }

        try {
            $token = $this->client()->refresh($connection->refresh_token);
        } catch (PontoException $e) {
            if ($e->status === 400 || $e->status === 401) {
                $connection->forceFill(['status' => PontoConnection::STATUS_NEEDS_REAUTH, 'last_error' => $e->getMessage()])->save();
                throw new PontoException('De bank vraagt om een nieuwe toestemming: autoriseer de koppeling opnieuw.', $e->status, $e->pontoCode, $e);
            }
            throw $e;
        }

        $connection->forceFill([
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'] ?? $connection->refresh_token,
            'token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 1800)),
            'status' => PontoConnection::STATUS_ACTIVE,
        ])->save();

        return (string) $token['access_token'];
    }

    /** Rekeningen bij Ponto ophalen en lokaal bijwerken; ingetrokken rekeningen verdwijnen. */
    public function refreshAccounts(PontoConnection $connection): void
    {
        $client = $this->client();
        $token = $this->accessToken($connection);
        $banks = [];
        $seen = [];
        $url = 'accounts';
        $query = ['page[limit]' => 100];

        do {
            $page = $client->get($token, $url, $query);
            $query = [];
            foreach ($page['data'] ?? [] as $item) {
                $a = (array) ($item['attributes'] ?? []);
                $bankId = $item['relationships']['financialInstitution']['data']['id'] ?? null;
                $bankName = $bankId ? ($banks[$bankId] ??= $this->institutionName($token, (string) $bankId)) : null;

                $account = PontoAccount::updateOrCreate(['company_id' => $connection->company_id, 'ponto_id' => $item['id']], [
                    'ponto_connection_id' => $connection->id,
                    'iban' => isset($a['reference']) ? strtoupper((string) preg_replace('/\s+/', '', (string) $a['reference'])) : null,
                    'name' => $a['description'] ?? $a['product'] ?? null,
                    'holder_name' => $a['holderName'] ?? null,
                    'bank_name' => $bankName,
                    'currency' => strtoupper((string) ($a['currency'] ?? 'EUR')),
                    'current_balance' => $a['currentBalance'] ?? null,
                    'available_balance' => $a['availableBalance'] ?? null,
                    'authorized_at' => isset($a['authorizedAt']) ? Carbon::parse($a['authorizedAt']) : null,
                    'authorization_expires_at' => isset($a['authorizationExpirationExpectedAt']) ? Carbon::parse($a['authorizationExpirationExpectedAt']) : null,
                ]);
                $seen[] = $account->id;
            }
            $url = $page['links']['next'] ?? null;
        } while ($url);

        PontoAccount::where('ponto_connection_id', $connection->id)->whereNotIn('id', $seen)->delete();
    }

    private function institutionName(string $token, string $id): ?string
    {
        try {
            $name = $this->client()->get($token, 'financial-institutions/' . $id)['data']['attributes']['name'] ?? null;

            return $name !== null ? mb_substr((string) $name, 0, 190) : null;
        } catch (PontoException) {
            return null;
        }
    }

    /** Koppeling verbreken: token intrekken bij Ponto, rekeningen weg; transacties blijven staan. */
    public function disconnect(PontoConnection $connection): void
    {
        try {
            if ($connection->refresh_token) {
                $this->client()->revoke($connection->refresh_token);
            }
        } catch (\Throwable $e) {
            Log::warning('Ponto: token intrekken mislukt', ['error' => $e->getMessage()]);
        }
        $connection->accounts()->delete();
        $connection->delete();
    }

    public static function base64url(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }
}
