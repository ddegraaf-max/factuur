<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class RunBackup extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Maak een database-back-up (pg_dump) en zet die op de S3-compatibele back-upopslag; ruim dumps ouder dan BACKUP_KEEP_DAYS op.';

    public function handle(BackupService $backups): int
    {
        if (! $backups->configured()) {
            $this->warn('Back-up niet ingericht: zet BACKUP_S3_ENDPOINT, BACKUP_S3_BUCKET, BACKUP_S3_KEY en BACKUP_S3_SECRET.');

            return self::SUCCESS;
        }

        $result = $backups->runSafely();
        if (! $result) {
            $this->error('Back-up mislukt — de eigenaar is per mail gealarmeerd; details in het log.');

            return self::FAILURE;
        }

        $this->info(sprintf('Back-up geslaagd: %s (%.1f MB), %d oude dump(s) opgeruimd.', $result['key'], $result['bytes'] / 1048576, $result['pruned']));

        return self::SUCCESS;
    }
}
