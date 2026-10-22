<?php

namespace App\Console\Commands;

use App\Services\Fachora\JobService;
use App\Support\Brand;
use Illuminate\Console\Command;

/**
 * Fachora 1.87: import op aanvraag. Elke minuut: bedrijven zoeken rond
 * klanten die nog niets te kiezen hebben, en dunne plekken aanvullen.
 */
class FachoraLocalSearch extends Command
{
    protected $signature = 'fachora:local-search {--max=3 : zoveel opdrachten per run}';

    protected $description = 'Fachora: bedrijven rond nieuwe klanten uit CEIDG halen (import op aanvraag)';

    public function handle(JobService $jobs): int
    {
        if (! Brand::is('fachora')) {
            $this->line('Niet het merk fachora; niets te doen.');

            return self::SUCCESS;
        }
        $result = $jobs->runLocalSearches(max(1, (int) $this->option('max')));
        $this->line("Wachtende klanten afgehandeld: {$result['searched']} · aangevuld: {$result['topped_up']}");

        return self::SUCCESS;
    }
}
