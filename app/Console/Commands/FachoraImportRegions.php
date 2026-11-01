<?php

namespace App\Console\Commands;

use App\Services\Fachora\CeidgClient;
use App\Support\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * De startregio's vullen uit CEIDG, in porties (1.86). De planner draait dit
 * elke tien minuten zolang FACHORA_REGION_IMPORT aan staat; elke run gaat
 * verder waar de vorige stopte. Alleen actieve bedrijven die minstens een
 * jaar bestaan, binnen de straal van een startregio en met een e-mailadres.
 *
 *   php artisan fachora:import-regions            één portie
 *   php artisan fachora:import-regions --status   alleen de voortgang tonen
 *   php artisan fachora:import-regions --reset    opnieuw beginnen bij de eerste regio
 */
class FachoraImportRegions extends Command
{
    protected $signature = 'fachora:import-regions {--firms= : bedrijven per run} {--status} {--reset}';

    protected $description = 'Fachora: startregio\'s vullen uit CEIDG (in porties)';

    public function handle(CeidgClient $ceidg): int
    {
        if (! Brand::is('fachora')) {
            $this->line('Niet het merk fachora; niets te doen.');

            return self::SUCCESS;
        }
        if ($this->option('reset')) {
            Cache::forget(CeidgClient::CURSOR);
            $this->line('Voortgang gewist.');
        }
        if ($this->option('status')) {
            $this->line(json_encode(Cache::get(CeidgClient::CURSOR, ['r' => 0, 'c' => 0, 'p' => 0, 'done' => false])));

            return self::SUCCESS;
        }
        if (! $ceidg->configured()) {
            $this->error('CEIDG_TOKEN ontbreekt.');

            return self::FAILURE;
        }

        $result = $ceidg->importRegionsStep($this->option('firms') !== null ? (int) $this->option('firms') : null);
        $this->line(sprintf('%d toegevoegd, %d overgeslagen, %d gecontroleerd; stand: %s', $result['created'], $result['skipped'], $result['checked'], $result['at']));

        return self::SUCCESS;
    }
}
