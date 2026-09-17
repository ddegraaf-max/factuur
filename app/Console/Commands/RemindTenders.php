<?php

namespace App\Console\Commands;

use App\Services\TenderService;
use Illuminate\Console\Command;

class RemindTenders extends Command
{
    protected $signature = 'tenders:remind';

    protected $description = 'Herinner onderaannemers die na drie dagen nog niet op een prijsaanvraag hebben gereageerd.';

    public function handle(TenderService $service): int
    {
        $count = $service->remindDue();
        $this->info("Verstuurd: {$count} herinnering(en) voor prijsaanvragen.");

        return self::SUCCESS;
    }
}
