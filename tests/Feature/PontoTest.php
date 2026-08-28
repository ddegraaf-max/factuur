<?php

namespace Tests\Feature;

use App\Models\BankTransaction;
use App\Models\PontoAccount;
use App\Models\PontoConnection;
use App\Services\Ponto\PontoException;
use App\Services\Ponto\PontoService;
use App\Services\Ponto\PontoSyncer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** Bankkoppeling via Ponto: autorisatie (PKCE), rekeningen, transacties ophalen en herautorisatie. */
class PontoTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private const ACCOUNT_ID = '2f1b6c3e-1111-4a5b-9c1d-000000000001';

    private function configure(): void
    {
        config(['services.ponto' => [
            'client_id' => 'd823671a-f6e2-40ea-aeff-86ed2c00e22e', 'client_secret' => 'geheim',
            'certificate' => "-----BEGIN CERTIFICATE-----\nMIIB\n-----END CERTIFICATE-----",
            'private_key' => "-----BEGIN RSA PRIVATE KEY-----\nMIIE\n-----END RSA PRIVATE KEY-----",
            'key_passphrase' => null, 'signature_certificate_id' => null, 'signature_private_key' => null,
            'signature_key_passphrase' => null, 'sandbox' => true, 'api_base' => 'https://api.ibanity.com/ponto-connect',
        ]]);
    }

    private function fakePonto(array $transactions): void
    {
        Http::fake(function ($request) use ($transactions) {
            $url = $request->url();

            return match (true) {
                str_contains($url, '/oauth2/token') => Http::response(['access_token' => 'at-1', 'refresh_token' => 'rt-1', 'expires_in' => 1800, 'scope' => 'ai offline_access']),
                str_contains($url, '/oauth2/revoke') => Http::response('', 200),
                str_contains($url, '/financial-institutions/') => Http::response(['data' => ['id' => 'fi-1', 'attributes' => ['name' => 'ABN AMRO']]]),
                str_contains($url, '/synchronizations') => Http::response(['data' => ['id' => 'sync-1', 'type' => 'synchronization', 'attributes' => ['status' => 'success']]], 201),
                str_contains($url, '/transactions') => Http::response(['data' => $transactions]),
                str_contains($url, '/accounts') => Http::response(['data' => [[
                    'id' => self::ACCOUNT_ID, 'type' => 'account',
                    'attributes' => [
                        'reference' => 'NL91 ABNA 0417 1643 00', 'referenceType' => 'IBAN', 'description' => 'Zakelijke rekening',
                        'currency' => 'EUR', 'currentBalance' => 1234.56, 'availableBalance' => 1200, 'holderName' => 'Vries Design B.V.',
                        'authorizedAt' => '2026-08-01T10:00:00Z', 'authorizationExpirationExpectedAt' => '2026-11-01T10:00:00Z',
                    ],
                    'relationships' => ['financialInstitution' => ['data' => ['id' => 'fi-1', 'type' => 'financialInstitution']]],
                ]]]),
                default => Http::response(['errors' => [['code' => 'resourceNotFound']]], 404),
            };
        });
    }

    private function transactions(): array
    {
        return [
            ['id' => 'tx-1', 'type' => 'transaction', 'attributes' => [
                'amount' => 250.5, 'currency' => 'EUR', 'executionDate' => now()->subDay()->toIso8601String(),
                'counterpartName' => 'Jansen Installatie', 'counterpartReference' => 'NL02 RABO 0123 4567 89',
                'remittanceInformation' => 'Factuur 2026-001', 'description' => 'SEPA overboeking',
            ]],
            ['id' => 'tx-old', 'type' => 'transaction', 'attributes' => [
                'amount' => -80, 'currency' => 'EUR', 'executionDate' => now()->subDays(200)->toIso8601String(),
                'counterpartName' => 'Oud', 'description' => 'Te oud voor de eerste import',
            ]],
        ];
    }

    public function test_connect_redirects_to_ponto_with_pkce(): void
    {
        $this->configure();
        $this->actingAs($this->demoUser());

        $response = $this->get(route('bank.ponto.connect'));
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('https://sandbox-authorization.myponto.com/oauth2/auth?', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $q);
        $this->assertSame('d823671a-f6e2-40ea-aeff-86ed2c00e22e', $q['client_id']);
        $this->assertSame('code', $q['response_type']);
        $this->assertSame('S256', $q['code_challenge_method']);
        $this->assertSame('ai offline_access', $q['scope']);
        $this->assertSame(route('bank.ponto.callback'), $q['redirect_uri']);

        $pending = session(PontoService::SESSION_KEY);
        $this->assertSame($pending['state'], $q['state']);
        $this->assertSame(PontoService::base64url(hash('sha256', $pending['verifier'], true)), $q['code_challenge']);
    }

    public function test_callback_stores_connection_accounts_and_imports_recent_transactions(): void
    {
        $this->configure();
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->fakePonto($this->transactions());

        $this->withSession([PontoService::SESSION_KEY => ['verifier' => 'verifier-1', 'state' => 'state-1', 'redirect_uri' => route('bank.ponto.callback')]])
            ->get(route('bank.ponto.callback', ['code' => 'code-1', 'state' => 'state-1']))
            ->assertRedirect(route('bank.index'));

        Http::assertSent(fn ($r) => str_contains($r->url(), '/oauth2/token')
            && str_starts_with($r->header('Authorization')[0] ?? '', 'Basic ')
            && str_contains($r->body(), 'grant_type=authorization_code')
            && str_contains($r->body(), 'code_verifier=verifier-1'));

        $connection = PontoConnection::where('company_id', $user->company_id)->firstOrFail();
        $this->assertSame(PontoConnection::STATUS_ACTIVE, $connection->status);
        $this->assertSame('at-1', $connection->access_token);

        $account = PontoAccount::where('company_id', $user->company_id)->firstOrFail();
        $this->assertSame('NL91ABNA0417164300', $account->iban);
        $this->assertSame('ABN AMRO', $account->bank_name);
        $this->assertSame(1234.56, (float) $account->current_balance);

        $imported = BankTransaction::where('source', 'ponto')->get();
        $this->assertCount(1, $imported, 'Alleen de recente transactie; de 200 dagen oude blijft buiten de eerste import.');
        $tx = $imported->first();
        $this->assertSame(250.5, (float) $tx->amount);
        $this->assertSame('NL02RABO0123456789', $tx->counterparty_iban);
        $this->assertSame('Factuur 2026-001 · SEPA overboeking', $tx->description);
        $this->assertSame($account->id, $tx->ponto_account_id);

        // Nogmaals synchroniseren geeft geen dubbele transacties.
        $this->assertSame(0, app(PontoSyncer::class)->sync($connection->fresh()));
        $this->assertSame(1, BankTransaction::where('source', 'ponto')->count());

        $this->get(route('bank.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('ponto.connected', true)
            ->where('ponto.accounts.0.bank_name', 'ABN AMRO')
            ->where('ponto.accounts.0.label', 'NL91 ABNA 0417 1643 00'));
    }

    public function test_sync_command_imports_for_active_connections(): void
    {
        $this->configure();
        $user = $this->demoUser();
        $connection = PontoConnection::create([
            'company_id' => $user->company_id, 'access_token' => 'at-1', 'refresh_token' => 'rt-1',
            'token_expires_at' => now()->addMinutes(20), 'status' => PontoConnection::STATUS_ACTIVE, 'sandbox' => true, 'connected_at' => now(),
        ]);
        $this->fakePonto($this->transactions());

        $this->artisan('ponto:sync')->assertExitCode(0);

        $this->assertSame(1, BankTransaction::withoutGlobalScope('company')->where('company_id', $user->company_id)->where('source', 'ponto')->count());
        $this->assertSame(1, PontoAccount::where('ponto_connection_id', $connection->id)->count());
        $this->assertNotNull($connection->fresh()->last_synced_at);
    }

    public function test_expired_refresh_token_marks_the_connection_for_reauthorization(): void
    {
        $this->configure();
        $user = $this->demoUser();
        $this->actingAs($user);
        $connection = PontoConnection::create([
            'company_id' => $user->company_id, 'access_token' => 'oud', 'refresh_token' => 'rt-oud',
            'token_expires_at' => now()->subMinute(), 'status' => PontoConnection::STATUS_ACTIVE, 'sandbox' => true,
        ]);
        Http::fake(fn () => Http::response(['error' => 'invalid_grant', 'error_description' => 'The refresh token has expired.'], 400));

        $failed = false;
        try {
            app(PontoSyncer::class)->sync($connection);
        } catch (PontoException $e) {
            $failed = true;
            $this->assertStringContainsString('nieuwe toestemming', $e->getMessage());
        }
        $this->assertTrue($failed);
        $this->assertSame(PontoConnection::STATUS_NEEDS_REAUTH, $connection->fresh()->status);

        $this->get(route('bank.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('ponto.status', 'needs_reauth')
            ->where('ponto.can_manage', true));
    }

    public function test_disconnect_revokes_the_token_and_keeps_transactions(): void
    {
        $this->configure();
        $user = $this->demoUser();
        $this->actingAs($user);
        $connection = PontoConnection::create(['company_id' => $user->company_id, 'access_token' => 'at-1', 'refresh_token' => 'rt-1', 'token_expires_at' => now()->addMinutes(20), 'status' => PontoConnection::STATUS_ACTIVE, 'sandbox' => true]);
        $this->fakePonto($this->transactions());
        app(PontoSyncer::class)->sync($connection);

        $this->post(route('bank.ponto.disconnect'))->assertRedirect();

        Http::assertSent(fn ($r) => str_contains($r->url(), '/oauth2/revoke') && str_contains($r->body(), 'token=rt-1'));
        $this->assertSame(0, PontoConnection::where('company_id', $user->company_id)->count());
        $this->assertSame(0, PontoAccount::where('company_id', $user->company_id)->count());
        $this->assertSame(1, BankTransaction::where('source', 'ponto')->whereNull('ponto_account_id')->count());
    }

    public function test_bank_page_hides_the_ponto_block_when_not_configured(): void
    {
        config(['services.ponto' => ['client_id' => null, 'client_secret' => null, 'certificate' => null, 'private_key' => null]]);
        $this->actingAs($this->demoUser());

        $this->get(route('bank.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('ponto.available', false)
            ->where('ponto.connected', false));
    }
}
