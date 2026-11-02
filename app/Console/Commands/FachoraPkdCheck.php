<?php

namespace App\Console\Commands;

use App\Services\Fachora\CeidgClient;
use App\Services\Fachora\RegisterNoticeService;
use App\Support\Brand;
use Illuminate\Console\Command;

/**
 * Fachora 1.96: hoofdactiviteit uit CEIDG bijwerken en bedrijven die geen
 * vakwerk als hoofdactiviteit hebben uit het register verwijderen (met een
 * regel in het verantwoordingslog). Elk uur, zodat zo'n bedrijf binnen een
 * dag uit elke keuzelijst is.
 *
 *   php artisan fachora:pkd-check --budget=25     zoveel detailopvragen per run
 *   php artisan fachora:pkd-check --dry-run       alleen tellen, niets verwijderen
 */
class FachoraPkdCheck extends Command
{
    protected $signature = 'fachora:pkd-check {--budget=25 : detailopvragen bij CEIDG per run} {--dry-run}';

    protected $description = 'Fachora: hoofdactiviteit (PKD) van registerbedrijven bijwerken en niet-passende bedrijven verwijderen';

    public function handle(CeidgClient $ceidg, RegisterNoticeService $notices): int
    {
        if (! Brand::is('fachora')) {
            $this->line('Niet het merk fachora; niets te doen.');

            return self::SUCCESS;
        }

        $refreshed = filled(config('fachora.ceidg_token')) ? $ceidg->refreshMainPkd(max(1, (int) $this->option('budget'))) : 0;
        $purged = $notices->purgeUnrelated((bool) $this->option('dry-run'));

        $this->line("Hoofdactiviteit bijgewerkt: {$refreshed} · " . ($this->option('dry-run') ? 'zou verwijderen: ' : 'verwijderd (geen vakwerk als hoofdactiviteit): ') . $purged);

        return self::SUCCESS;
    }
}
