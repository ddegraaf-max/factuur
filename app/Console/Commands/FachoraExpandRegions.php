<?php

namespace App\Console\Commands;

use App\Services\Fachora\CeidgClient;
use App\Support\Brand;
use Illuminate\Console\Command;

/**
 * Fachora 1.87: elke dag één grote stad (met region_radius_km eromheen) erbij,
 * zodat de database stap voor stap heel Polen dekt. De import zelf blijft
 * fachora:import-regions (elke tien minuten een portie).
 *
 *   php artisan fachora:expand-regions          de volgende stad erbij
 *   php artisan fachora:expand-regions --list   welke regio's actief zijn
 */
class FachoraExpandRegions extends Command
{
    protected $signature = 'fachora:expand-regions {--list}';

    protected $description = 'Fachora: de volgende stad aan de regio-import toevoegen';

    public function handle(CeidgClient $ceidg): int
    {
        if (! Brand::is('fachora')) {
            $this->line('Niet het merk fachora; niets te doen.');

            return self::SUCCESS;
        }
        if (! $this->option('list')) {
            $region = $ceidg->expandRegions();
            $this->line($region ? 'Erbij: ' . $region['name'] : 'Alle steden uit de lijst zijn al actief.');
        }
        $this->line('Actief: ' . implode(', ', array_map(fn ($r) => $r['name'], CeidgClient::regions())));

        return self::SUCCESS;
    }
}
