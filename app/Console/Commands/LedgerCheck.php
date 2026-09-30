<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\LedgerReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Controleert het grootboek, zonder de database op haar woord te geloven.
 *
 * ── Waarom dit bestaat terwijl de database het al afdwingt ────────────────
 *
 * Op Postgres kan een journaalpost niet uit balans staan: er zit een
 * constraint-trigger op. Maar de tests van dit pakket draaien op SQLite, en
 * SQLite kent geen uitgestelde controle. Daar is de garantie dus die van de
 * bibliotheek, en dan wil je iets dat het naderhand nakijkt.
 *
 * En er is een tweede reden: een controle die los van het mechanisme staat, is
 * de enige die een fout in dat mechanisme kan vinden. Deze opdracht telt de
 * regels zelf op en vergelijkt de proefbalans met de journaalposten. Draai hem
 * na een import, na een herbouw, en vóór het vaststellen van een boekjaar.
 */
class LedgerCheck extends Command
{
    protected $signature = 'ledger:check
        {company? : het id van de administratie; laat weg voor alle}
        {--year= : alleen dit boekjaar}';

    protected $description = 'Controleert of elke journaalpost in balans is en of de proefbalans sluit.';

    public function handle(LedgerReportService $reports): int
    {
        $companies = $this->argument('company')
            ? Company::where('id', $this->argument('company'))->get()
            : Company::orderBy('id')->get();

        $problemen = 0;

        foreach ($companies as $company) {
            $posten = DB::table('journal_entries')->where('company_id', $company->id)->count();
            if ($posten === 0) {
                continue;
            }

            $this->line("— {$company->name} (#{$company->id}): {$posten} journaalpost(en)");

            // 1. Elke post afzonderlijk in balans.
            $scheef = DB::table('journal_entries')
                ->leftJoin('journal_lines', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.company_id', $company->id)
                ->when($this->option('year'), fn ($q) => $q->where('journal_entries.year', (int) $this->option('year')))
                ->groupBy('journal_entries.id', 'journal_entries.number')
                ->havingRaw('COALESCE(SUM(journal_lines.debit_cents),0) <> COALESCE(SUM(journal_lines.credit_cents),0)')
                ->select('journal_entries.id', 'journal_entries.number')
                ->selectRaw('COALESCE(SUM(journal_lines.debit_cents),0) AS d, COALESCE(SUM(journal_lines.credit_cents),0) AS c')
                ->get();

            foreach ($scheef as $post) {
                $problemen++;
                $this->error(sprintf('  boekstuk %s staat niet in balans: debet %d cent, credit %d cent',
                    $post->number, $post->d, $post->c));
            }

            // 2. Elke regel aan één kant, en niet leeg.
            $rare = DB::table('journal_lines')
                ->where('company_id', $company->id)
                ->where(function ($q) {
                    $q->where(function ($q) {
                        $q->where('debit_cents', '>', 0)->where('credit_cents', '>', 0);
                    })->orWhere(function ($q) {
                        $q->where('debit_cents', 0)->where('credit_cents', 0);
                    })->orWhere('debit_cents', '<', 0)->orWhere('credit_cents', '<', 0);
                })
                ->count();

            if ($rare > 0) {
                $problemen++;
                $this->error("  {$rare} regel(s) staan aan twee kanten, zijn leeg of negatief");
            }

            // 3. Geboekt op een rubriek waarop niet geboekt mag worden.
            $oprubriek = DB::table('journal_lines')
                ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
                ->where('journal_lines.company_id', $company->id)
                ->where('ledger_accounts.postable', false)
                ->count();

            if ($oprubriek > 0) {
                $problemen++;
                $this->error("  {$oprubriek} regel(s) staan op een rubriek in plaats van op een rekening");
            }

            // 4. De proefbalans per boekjaar.
            $jaren = $this->option('year')
                ? [(int) $this->option('year')]
                : DB::table('journal_entries')->where('company_id', $company->id)
                    ->distinct()->orderBy('year')->pluck('year')->all();

            foreach ($jaren as $jaar) {
                $proef = $reports->trialBalance(
                    $company,
                    Carbon::create($jaar, 1, 1)->startOfDay(),
                    Carbon::create($jaar, 12, 31)->endOfDay()
                );

                if (! $proef['totals']['balanced']) {
                    $problemen++;
                    $this->error(sprintf('  proefbalans %d sluit niet: debet %d cent, credit %d cent',
                        $jaar, $proef['totals']['debit'], $proef['totals']['credit']));

                    continue;
                }

                // 5. De balans zelf: bezittingen tegen schulden plus vermogen.
                $balans = $reports->balanceSheet($company, $jaar);
                if (! $balans['totals']['balanced']) {
                    $problemen++;
                    $this->error(sprintf('  balans %d sluit niet: actief %d cent, passief %d cent',
                        $jaar, $balans['totals']['assets'], $balans['totals']['liabilities']));

                    continue;
                }

                $this->line(sprintf('  %d: proefbalans en balans sluiten (€ %s omgezet)',
                    $jaar, number_format($proef['totals']['debit'] / 100, 2, ',', '.')));
            }
        }

        if ($problemen > 0) {
            $this->newLine();
            $this->error("{$problemen} probleem/problemen gevonden.");

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Het grootboek klopt.');

        return self::SUCCESS;
    }
}
