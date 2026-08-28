<?php

namespace Tests\Feature;

use App\Http\Controllers\HealthController;
use App\Services\BackupService;
use App\Support\S3Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Dagelijkse back-up: dump → S3-compatibele opslag → opruimen → status voor /health. */
class BackupTest extends TestCase
{
    use RefreshDatabase;

    private function configure(): void
    {
        config(['services.backup' => [
            'endpoint' => 'https://example.r2.cloudflarestorage.com', 'region' => 'auto', 'bucket' => 'easyinvoice-backups',
            'key' => 'AKIAEXAMPLE', 'secret' => 'geheim', 'prefix' => 'prod', 'keep_days' => 30,
            'dump_command' => 'printf "PGDMP-testdump-%0200d" 0',
        ]]);
    }

    public function test_requests_are_signed_with_aws_signature_v4(): void
    {
        $client = new S3Client('https://example.r2.cloudflarestorage.com', 'auto', 'easyinvoice-backups', 'AKIAEXAMPLE', 'geheim');
        $headers = $client->signedHeaders('PUT', 'prod/easyinvoice-2026-08-29-0330.dump', [], 'inhoud', ['content-type' => 'application/octet-stream'], new \DateTimeImmutable('2026-08-29 03:30:00', new \DateTimeZone('UTC')));

        $this->assertSame('20260829T033000Z', $headers['x-amz-date']);
        $this->assertSame(hash('sha256', 'inhoud'), $headers['x-amz-content-sha256']);
        $this->assertStringStartsWith('AWS4-HMAC-SHA256 Credential=AKIAEXAMPLE/20260829/auto/s3/aws4_request, SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date, Signature=', $headers['Authorization']);
        $this->assertMatchesRegularExpression('/Signature=[0-9a-f]{64}$/', $headers['Authorization']);
        $this->assertSame('https://example.r2.cloudflarestorage.com/easyinvoice-backups/prod/easyinvoice-2026-08-29-0330.dump', $client->url('prod/easyinvoice-2026-08-29-0330.dump'));
    }

    public function test_backup_uploads_the_dump_prunes_old_ones_and_updates_health(): void
    {
        $this->configure();
        Cache::forget(BackupService::LAST_OK_KEY);
        $old = now('UTC')->subDays(45)->format('Y-m-d');
        $listing = '<?xml version="1.0" encoding="UTF-8"?><ListBucketResult xmlns="http://s3.amazonaws.com/doc/2006-03-01/"><Name>easyinvoice-backups</Name><Contents><Key>prod/easyinvoice-' . $old . '-0330.dump</Key><Size>10</Size></Contents><Contents><Key>prod/easyinvoice-' . now('UTC')->format('Y-m-d') . '-0100.dump</Key><Size>10</Size></Contents></ListBucketResult>';
        Http::fake(fn ($request) => $request->method() === 'GET' && str_contains($request->url(), 'list-type=2')
            ? Http::response($listing, 200, ['Content-Type' => 'application/xml'])
            : Http::response('', 200));

        $this->artisan('backup:run')->assertExitCode(0);

        Http::assertSent(fn ($r) => $r->method() === 'PUT' && str_contains($r->url(), '/easyinvoice-backups/prod/easyinvoice-') && str_contains($r->body(), 'PGDMP-testdump') && str_starts_with($r->header('Authorization')[0] ?? '', 'AWS4-HMAC-SHA256'));
        Http::assertSent(fn ($r) => $r->method() === 'DELETE' && str_contains($r->url(), "easyinvoice-{$old}-0330.dump"));
        Http::assertNotSent(fn ($r) => $r->method() === 'DELETE' && str_contains($r->url(), now('UTC')->format('Y-m-d') . '-0100.dump'));

        $this->assertNotNull(Cache::get(BackupService::LAST_OK_KEY));
        Cache::forever(HealthController::HEARTBEAT_KEY, now()->timestamp);
        $this->getJson('/health')->assertOk()->assertJsonPath('checks.backup.ok', true);
    }

    public function test_health_reports_a_stale_backup_when_configured(): void
    {
        $this->configure();
        Cache::forever(HealthController::HEARTBEAT_KEY, now()->timestamp);
        Cache::forever(BackupService::LAST_OK_KEY, now()->subHours(50)->timestamp);

        $this->getJson('/health')->assertStatus(503)->assertJsonPath('checks.backup.ok', false);
    }
}
