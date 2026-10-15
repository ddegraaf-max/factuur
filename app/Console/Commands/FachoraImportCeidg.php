<?php

namespace App\Console\Commands;

use App\Services\Fachora\CeidgClient;
use Illuminate\Console\Command;

/**
 * Bedrijven ophalen uit CEIDG op PKD-code en gebied (CEIDG_TOKEN nodig).
 *
 *   php artisan fachora:import-ceidg 43.22.Z 43.21.Z --wojewodztwo=łódzkie
 *   php artisan fachora:import-ceidg 43.22.Z --miasto=Skierniewice --pages=5
 *
 * Ze komen als 'unclaimed' in de database; Fachora mailt ze niet.
 */
class FachoraImportCeidg extends Command
{
    protected $signature = 'fachora:import-ceidg {pkd* : PKD-codes, bijv. 43.22.Z} {--wojewodztwo=} {--miasto=} {--pages=20}';

    protected $description = 'Fachora: bedrijven importeren uit CEIDG op PKD-code en gebied';

    public function handle(CeidgClient $ceidg): int
    {
        if (! $ceidg->configured()) {
            $this->error('CEIDG_TOKEN ontbreekt. Vraag een token aan op dane.biznes.gov.pl en zet het in de omgeving.');

            return self::FAILURE;
        }

        foreach ((array) $this->argument('pkd') as $pkd) {
            $result = $ceidg->import((string) $pkd, $this->option('wojewodztwo') ?: null, $this->option('miasto') ?: null, (int) $this->option('pages'));
            $this->line(sprintf('%s: %d toegevoegd, %d overgeslagen, %d pagina\'s', $pkd, $result['created'], $result['skipped'], $result['pages']));
        }

        return self::SUCCESS;
    }
}
