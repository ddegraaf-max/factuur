<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\ChartOfAccountsService;
use App\Services\LedgerPostingService;
use Illuminate\Console\Command;

/**
 * Zet het grootboek aan voor een administratie: het rekeningschema erin, de
 * dagboeken erbij, en wat er al is geboekt.
 *
 * Bedoeld voor administraties die er al waren voordat het grootboek bestond. Een
 * nieuwe administratie krijgt het schema bij het aanmaken.
 */
class LedgerSetup extends Command
{
    protected $signature = 'ledger:setup
        {company? : het id van de administratie; laat weg voor alle}
        {--year= : alleen dit boekjaar naboeken}
        {--no-rebuild : alleen het schema aanleggen, nog niet boeken}';

    protected $description = 'Legt het rekeningschema aan en boekt de bestaande facturen in het grootboek.';

    public function handle(ChartOfAccountsService $chart, LedgerPostingService $posting): int
    {
        $companies = $this->argument('company')
            ? Company::where('id', $this->argument('company'))->get()
            : Company::orderBy('id')->get();

        if ($companies->isEmpty()) {
            $this->error('Geen administratie gevonden.');

            return self::FAILURE;
        }

        $jaar = $this->option('year') ? (int) $this->option('year') : null;

        foreach ($companies as $company) {
            $this->line("— {$company->name} (#{$company->id})");

            $telling = $chart->seed($company);
            $this->line(sprintf('  schema: %d rekening(en), %d dagboek(en) aangelegd',
                $telling['accounts'], $telling['journals']));

            if ($this->option('no-rebuild')) {
                continue;
            }

            // Het schema is er nu pas; de service heeft hem nog niet gezien.
            app(\App\Services\LedgerService::class)->forget($company);

            $uit = $posting->rebuild($company, $jaar);
            $this->line(sprintf(
                '  geboekt: %d factu(u)r(en), %d inkoop, %d ontvangst(en), %d betaling(en) inkoop',
                $uit['invoices'], $uit['purchases'], $uit['payments'], $uit['purchase_payments']
            ));

            foreach ($uit['errors'] as $fout) {
                $this->warn('  ! ' . $fout);
            }
        }

        return self::SUCCESS;
    }
}
