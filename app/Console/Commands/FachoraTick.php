<?php

namespace App\Console\Commands;

use App\Services\Fachora\JobService;
use App\Services\Fachora\ProviderService;
use App\Support\Brand;
use Illuminate\Console\Command;

/**
 * Elk uur: herinneringen aan bedrijven, verlopen reactietermijnen (volgende
 * ronde), stilzwijgende goedkeuring, opruimen van onbevestigde aanvragen en
 * geocoderen van bedrijven zonder coördinaten. Alleen onder het merk fachora.
 */
class FachoraTick extends Command
{
    protected $signature = 'fachora:tick';

    protected $description = 'Fachora: herinneringen, rondes, automatische goedkeuring, opruimen en geocoderen';

    public function handle(JobService $jobs, ProviderService $providers): int
    {
        if (! Brand::is('fachora')) {
            $this->line('Niet het merk fachora; niets te doen.');

            return self::SUCCESS;
        }

        $this->line('Herinneringen: ' . $jobs->remindOffers());
        $this->line('Verlopen rondes: ' . $jobs->expireOffers());
        $this->line('Automatisch goedgekeurd: ' . $jobs->autoAccept());
        $this->line('Opgeruimd (onbevestigd): ' . $jobs->purgeUnconfirmed());
        $this->line('Aanvragen na betaling verstuurd: ' . $jobs->syncPendingRequests());
        $this->line('Verlopen (geen keuze): ' . $jobs->expireSelecting());
        $this->line('Gegeocodeerd: ' . $providers->geocodePending(30));

        return self::SUCCESS;
    }
}
