<?php

namespace App\Services;

use App\Support\ErrorAlert;
use App\Support\S3Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Dagelijkse database-back-up: pg_dump (custom format, gecomprimeerd) naar
 * S3-compatibele opslag, oude dumps opruimen, status voor /health bijhouden
 * en bij falen de eigenaar alarmeren. Herstellen: pg_restore --clean --no-owner -d <db> bestand.dump
 */
class BackupService
{
    public const LAST_OK_KEY = 'backup.last_ok';

    public function configured(): bool
    {
        return S3Client::fromConfig() !== null;
    }

    /** @return array{key: string, bytes: int, pruned: int} */
    public function run(): array
    {
        $s3 = S3Client::fromConfig();
        if (! $s3) {
            throw new \RuntimeException('Back-up niet ingericht: BACKUP_S3_* ontbreekt.');
        }

        $dump = $this->dump();
        $prefix = trim((string) config('services.backup.prefix'), '/');
        $key = ($prefix ? $prefix . '/' : '') . 'easyinvoice-' . now('UTC')->format('Y-m-d-Hi') . '.dump';

        $s3->put($key, $dump, 'application/octet-stream');
        $pruned = $this->prune($s3, $prefix);

        Cache::forever(self::LAST_OK_KEY, now()->timestamp);
        Log::info('Back-up geslaagd', ['key' => $key, 'bytes' => strlen($dump), 'opgeruimd' => $pruned]);

        return ['key' => $key, 'bytes' => strlen($dump), 'pruned' => $pruned];
    }

    /** Draai de back-up en vertaal falen naar een alarmmail (gedoseerd) i.p.v. een stille crash. */
    public function runSafely(): ?array
    {
        try {
            return $this->run();
        } catch (\Throwable $e) {
            Log::error('Back-up mislukt', ['error' => $e->getMessage()]);
            ErrorAlert::report(new \RuntimeException('Dagelijkse back-up mislukt: ' . $e->getMessage(), 0, $e));

            return null;
        }
    }

    protected function dump(): string
    {
        $custom = config('services.backup.dump_command');
        if ($custom) {
            $process = Process::fromShellCommandline($custom, base_path(), null, null, 600);
        } else {
            $db = config('database.connections.' . config('database.default'));
            $process = new Process([
                'pg_dump', '--format=custom', '--compress=6', '--no-owner', '--no-privileges',
                '--host=' . ($db['host'] ?? '127.0.0.1'), '--port=' . ($db['port'] ?? 5432),
                '--username=' . ($db['username'] ?? ''), '--dbname=' . ($db['database'] ?? ''),
            ], base_path(), ['PGPASSWORD' => (string) ($db['password'] ?? ''), 'PATH' => getenv('PATH') ?: '/usr/bin:/bin'], null, 900);
        }

        $process->run();
        if (! $process->isSuccessful()) {
            throw new \RuntimeException('pg_dump: ' . trim($process->getErrorOutput() ?: $process->getOutput()));
        }
        $output = $process->getOutput();
        if (strlen($output) < 100) {
            throw new \RuntimeException('pg_dump gaf een (bijna) lege dump terug.');
        }

        return $output;
    }

    /** Verwijder dumps ouder dan keep_days; geeft het aantal verwijderde bestanden terug. */
    protected function prune(S3Client $s3, string $prefix): int
    {
        $keepDays = max(1, (int) config('services.backup.keep_days', 30));
        $cutoff = now('UTC')->subDays($keepDays);
        $pruned = 0;
        foreach ($s3->list($prefix ? $prefix . '/' : '') as $object) {
            if (! preg_match('/easyinvoice-(\d{4}-\d{2}-\d{2})-\d{4}\.dump$/', $object['key'], $m)) {
                continue;
            }
            if (\Carbon\Carbon::createFromFormat('Y-m-d', $m[1], 'UTC')->lt($cutoff)) {
                $s3->delete($object['key']);
                $pruned++;
            }
        }

        return $pruned;
    }
}
