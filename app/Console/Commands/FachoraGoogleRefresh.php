<?php

namespace App\Console\Commands;

use App\Services\Fachora\GooglePlacesClient;
use App\Support\Brand;
use Illuminate\Console\Command;

/**
 * Fachora 1.103: Google-bedrijven verversen (Places-beleid: opgeslagen gegevens binnen 30 dagen
 * verversen of weggooien). Dagelijks; gesloten of verdwenen bedrijven gaan uit de lijst.
 */
class FachoraGoogleRefresh extends Command
{
    protected $signature = 'fachora:google-refresh {--limit=150 : zoveel bedrijven per run}';

    protected $description = 'Fachora: beoordelingen en contactgegevens van Google-bedrijven verversen';

    public function handle(GooglePlacesClient $google): int
    {
        if (! Brand::is('fachora')) {
            $this->line('Niet het merk fachora; niets te doen.');

            return self::SUCCESS;
        }
        $result = $google->refreshStale(max(1, (int) $this->option('limit')));
        $this->line("Ververst: {$result['refreshed']}, uit de lijst: {$result['removed']}, mislukt: {$result['failed']}.");

        return self::SUCCESS;
    }
}
