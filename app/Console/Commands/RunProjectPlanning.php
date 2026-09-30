<?php

namespace App\Console\Commands;

use App\Services\ProjectPlanService;
use Illuminate\Console\Command;

class RunProjectPlanning extends Command
{
    protected $signature = 'projects:plan';

    protected $description = 'Vooraankondiging en herinnering aan onderaannemers op de projectplanning; op maandag het weekoverzicht voor de ondernemer.';

    public function handle(ProjectPlanService $service): int
    {
        $count = $service->runDaily();
        $this->info("Vooraankondigingen: {$count['headsup']}, herinneringen: {$count['reminders']}, weekoverzichten: {$count['digests']}.");

        return self::SUCCESS;
    }
}
