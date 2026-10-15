<?php

namespace App\Console\Commands;

use App\Services\Fachora\VerificationService;
use App\Support\Brand;
use Illuminate\Console\Command;

/** Wekelijks: staan de Verified bedrijven nog goed in het register, en zijn de polissen nog geldig? */
class FachoraChecks extends Command
{
    protected $signature = 'fachora:checks';

    protected $description = 'Fachora: wekelijkse hercontrole van Verified bedrijven (NIP, verzekering)';

    public function handle(VerificationService $verification): int
    {
        if (! Brand::is('fachora')) {
            return self::SUCCESS;
        }
        $this->line('Bedrijven met een wijziging: ' . $verification->recheckAll());

        return self::SUCCESS;
    }
}
