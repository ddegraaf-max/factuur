<?php

namespace App\Console\Commands;

use App\Services\RecurringInvoiceService;
use Illuminate\Console\Command;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring';

    protected $description = 'Genereer facturen uit terugkerende profielen die aan de beurt zijn.';

    public function handle(RecurringInvoiceService $service): int
    {
        $count = $service->runDue();
        $this->info("Gegenereerd: {$count} terugkerende factu(u)r(en).");

        return self::SUCCESS;
    }
}
