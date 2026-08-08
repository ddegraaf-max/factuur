<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature = 'invoices:remind';

    protected $description = 'Verstuur automatisch betalingsherinneringen en aanmaningen voor achterstallige facturen.';

    public function handle(ReminderService $service): int
    {
        $count = $service->run();
        $this->info("Verstuurd: {$count} herinnering(en)/aanmaning(en).");

        return self::SUCCESS;
    }
}
