<?php

namespace App\Console\Commands;

use App\Services\BrandEvidenceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Maandelijks merkgebruik-dossier (bewijs van normaal gebruik van het merk).
 *   php artisan brand:evidence            → vorige maand
 *   php artisan brand:evidence --month=2026-08
 */
class BuildBrandEvidence extends Command
{
    protected $signature = 'brand:evidence {--month= : Maand als YYYY-MM (standaard: vorige maand)} {--no-mail : Alleen bestanden, niet mailen}';

    protected $description = 'Bouw het maandelijkse merkgebruik-dossier en mail het naar de eigenaar.';

    public function handle(BrandEvidenceService $service): int
    {
        $month = $this->option('month')
            ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth()
            : now()->subMonthNoOverflow()->startOfMonth();

        $dossier = $service->buildMonth($month, mail: ! $this->option('no-mail'));

        $this->info("Dossier {$dossier->month} opgesteld: " . count($dossier->manifest) . ' bestanden'
            . ($dossier->mailed_to ? ", gemaild naar {$dossier->mailed_to}." : '.'));

        return self::SUCCESS;
    }
}
