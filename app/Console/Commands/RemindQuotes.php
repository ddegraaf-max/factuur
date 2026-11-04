<?php

namespace App\Console\Commands;

use App\Services\QuoteFollowUpService;
use Illuminate\Console\Command;

/**
 * 1.102: offerteopvolging. Dagelijks: stuurt de klant een herinnering aan een nog openstaande
 * offerte op de geplande momenten (een paar dagen na het versturen en vlak vóór de vervaldatum).
 */
class RemindQuotes extends Command
{
    protected $signature = 'quotes:remind';

    protected $description = 'Stuur de geplande herinneringen voor openstaande offertes';

    public function handle(QuoteFollowUpService $service): int
    {
        $n = $service->runDue();
        $this->info($n . ' herinnering(en) verstuurd.');

        return self::SUCCESS;
    }
}
