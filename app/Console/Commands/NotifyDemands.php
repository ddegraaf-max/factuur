<?php

namespace App\Console\Commands;

use App\Services\PaymentDemandService;
use Illuminate\Console\Command;

class NotifyDemands extends Command
{
    protected $signature = 'demands:notify';

    protected $description = 'Meld ondernemers dat de termijn van een online aanmaning voorbij is, en sluit aanmaningen van betaalde facturen.';

    public function handle(PaymentDemandService $service): int
    {
        $count = $service->notifyExpired();
        $this->info("Verstuurd: {$count} melding(en) over een verstreken termijn.");

        return self::SUCCESS;
    }
}
