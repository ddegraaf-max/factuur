<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\MollieSettlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Haalt dagelijks voor elke administratie met een Mollie-koppeling op welke
 * online betalingen inmiddels in een uitbetaling zitten (rapport
 * Mollie-betalingen). Draait via de scheduler (routes/console.php).
 */
class SyncMollieSettlements extends Command
{
    protected $signature = 'mollie:settlements {--force : Ook betalingen die korter dan zes uur geleden zijn gecontroleerd}';

    protected $description = 'Haal bij Mollie op in welke uitbetaling elke online betaling zit.';

    public function handle(MollieSettlementService $service): int
    {
        $companies = Company::whereNotNull('mollie_api_key')
            ->where('is_demo', false)
            ->get();

        $checked = 0;
        foreach ($companies as $company) {
            try {
                $result = $service->sync($company, (bool) $this->option('force'));
                $checked += $result['checked'];
            } catch (\Throwable $e) {
                Log::error('Mollie-uitbetalingen ophalen mislukt', ['company' => $company->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Gecontroleerd: {$checked} betaling(en) bij {$companies->count()} administratie(s).");

        return self::SUCCESS;
    }
}
