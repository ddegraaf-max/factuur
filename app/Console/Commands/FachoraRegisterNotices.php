<?php

namespace App\Console\Commands;

use App\Services\Fachora\RegisterNoticeService;
use App\Support\Brand;
use Illuminate\Console\Command;

/**
 * Fachora 1.87: de informatiemail (art. 14 AVG) aan bedrijven uit het
 * register, in kleine porties. De planner draait dit op werkdagen elke 30
 * minuten tussen 8 en 18 uur (Warschau).
 *
 *   php artisan fachora:register-notices            één portie
 *   php artisan fachora:register-notices --status   wachtrij en stand van vandaag
 */
class FachoraRegisterNotices extends Command
{
    protected $signature = 'fachora:register-notices {--max= : hooguit zoveel mails} {--status}';

    protected $description = 'Fachora: informatiemail (art. 14 AVG) aan bedrijven uit het register, in porties';

    public function handle(RegisterNoticeService $notices): int
    {
        if (! Brand::is('fachora')) {
            $this->line('Niet het merk fachora; niets te doen.');

            return self::SUCCESS;
        }
        if (! $this->option('status')) {
            $r = $notices->sendBatch($this->option('max') !== null ? (int) $this->option('max') : null);
            $this->line("Verstuurd: {$r['sent']} · mislukt: {$r['failed']} · vandaag nog: {$r['left_today']}");
        }
        $this->line('Wachtrij: ' . $notices->backlog() . ' · vandaag verstuurd: ' . $notices->sentToday() . ' · import gepauzeerd: ' . ($notices->importShouldPause() ? 'ja' : 'nee'));

        return self::SUCCESS;
    }
}
