<?php

namespace App\Console\Commands;

use App\Services\Fachora\RegisterNoticeService;
use App\Support\Brand;
use Illuminate\Console\Command;

/**
 * Fachora 1.87 (optie 2): bedrijven uit het register die niemand koos en die
 * nooit een bericht van ons kregen, verwijderen. Dagelijks met
 * register_keep_days; eenmalig voor oude imports met --days=0.
 *
 *   php artisan fachora:purge-register --dry-run          alleen tellen
 *   php artisan fachora:purge-register --days=0           alles wat ongebruikt is, nu
 */
class FachoraPurgeRegister extends Command
{
    protected $signature = 'fachora:purge-register {--days= : ouder dan zoveel dagen} {--dry-run}';

    protected $description = 'Fachora: ongebruikte bedrijven uit het register verwijderen';

    public function handle(RegisterNoticeService $notices): int
    {
        if (! Brand::is('fachora')) {
            $this->line('Niet het merk fachora; niets te doen.');

            return self::SUCCESS;
        }
        $days = $this->option('days') !== null ? (int) $this->option('days') : (int) config('fachora.register_keep_days', 21);
        $n = $notices->purgeUnusedRegister($days, (bool) $this->option('dry-run'));
        $this->line(($this->option('dry-run') ? 'Zou verwijderen: ' : 'Verwijderd: ') . $n . " (ouder dan {$days} dagen)");

        return self::SUCCESS;
    }
}
