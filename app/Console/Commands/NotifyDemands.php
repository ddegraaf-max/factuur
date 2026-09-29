<?php

namespace App\Console\Commands;

use App\Services\PaymentDemandService;
use App\Services\PublicDemandService;
use Illuminate\Console\Command;

class NotifyDemands extends Command
{
    protected $signature = 'demands:notify';

    protected $description = 'Meld dat de termijn van een online aanmaning voorbij is, sluit aanmaningen van betaalde facturen en ruim onbevestigde aanmaningen op.';

    public function handle(PaymentDemandService $service, PublicDemandService $public): int
    {
        $count = $service->notifyExpired();
        $purged = $public->purgeUnconfirmed();
        $this->info("Verstuurd: {$count} melding(en) over een verstreken termijn; {$purged} onbevestigde aanmaning(en) opgeruimd.");

        return self::SUCCESS;
    }
}
