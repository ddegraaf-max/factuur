<?php

namespace App\Console\Commands;

use App\Services\Fachora\PkdTranslator;
use App\Support\Brand;
use Illuminate\Console\Command;

/**
 * Fachora 1.100: de Poolse hoofdactiviteit (PKD) van registerbedrijven naar het Engels
 * vertalen, voor klanten die geen Pools lezen. Elke tien minuten; alleen een AI-aanroep
 * als er nog namen zonder vertaling zijn.
 *
 *   php artisan fachora:pkd-translate --budget=40     zoveel unieke namen per run
 */
class FachoraPkdTranslate extends Command
{
    protected $signature = 'fachora:pkd-translate {--budget=40 : unieke namen per run}';

    protected $description = 'Fachora: Poolse hoofdactiviteit (PKD) van registerbedrijven naar het Engels vertalen';

    public function handle(PkdTranslator $translator): int
    {
        if (! Brand::is('fachora')) {
            $this->line('Niet het merk fachora; niets te doen.');

            return self::SUCCESS;
        }
        $n = $translator->fill(max(1, (int) $this->option('budget')));
        $this->line("Hoofdactiviteit vertaald voor {$n} bedrijven.");

        return self::SUCCESS;
    }
}
