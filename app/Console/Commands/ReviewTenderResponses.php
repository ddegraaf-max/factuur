<?php

namespace App\Console\Commands;

use App\Services\TenderReviewService;
use Illuminate\Console\Command;

class ReviewTenderResponses extends Command
{
    protected $signature = 'tenders:review';

    protected $description = 'Offertecheck: beoordeel binnengekomen prijsopgaven van onderaannemers en mail de ondernemer het advies.';

    public function handle(TenderReviewService $service): int
    {
        $count = $service->reviewPending();
        $this->info("Beoordeeld: {$count} prijsopgave(n).");

        return self::SUCCESS;
    }
}
