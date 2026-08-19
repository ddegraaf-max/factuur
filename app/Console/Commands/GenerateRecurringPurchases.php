<?php

namespace App\Console\Commands;

use App\Services\RecurringPurchaseService;
use Illuminate\Console\Command;

class GenerateRecurringPurchases extends Command
{
    protected $signature = 'purchases:generate-recurring';

    protected $description = 'Boek inkoopfacturen in uit terugkerende-inkoopprofielen (vaste lasten) die aan de beurt zijn.';

    public function handle(RecurringPurchaseService $service): int
    {
        $count = $service->runDue();
        $this->info("Ingeboekt: {$count} terugkerende inkoopfactu(u)r(en).");

        return self::SUCCESS;
    }
}
