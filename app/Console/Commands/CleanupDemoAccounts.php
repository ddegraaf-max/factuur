<?php

namespace App\Console\Commands;

use App\Services\DemoCleaner;
use Illuminate\Console\Command;

class CleanupDemoAccounts extends Command
{
    protected $signature = 'demo:cleanup';

    protected $description = 'Verwijder verlopen demo-omgevingen met alle bijbehorende voorbeeldgegevens.';

    public function handle(DemoCleaner $cleaner): int
    {
        $count = $cleaner->purgeExpired();
        $this->info("Opgeruimd: {$count} demo-omgeving(en).");

        return self::SUCCESS;
    }
}
