<?php

namespace App\Console\Commands;

use App\Services\MerkenService;
use Illuminate\Console\Command;

/**
 * Haalt per merk van het VvE/NextPro-pakket het extra verbruik op (bundels en
 * brieven per post) en zet per klant een conceptfactuur klaar in de
 * administratie van het merk. Niets wordt verstuurd. Zie MerkenService.
 *
 *   php artisan merken:bundels-factureren            alle merken
 *   php artisan merken:bundels-factureren nextpro    één merk
 */
class MerkenBundelsFactureren extends Command
{
    protected $signature = 'merken:bundels-factureren {merk? : code van één merk, bijv. nextpro}';

    protected $description = 'Haalt extra verbruik op bij de merken van het VvE-pakket en zet conceptfacturen klaar.';

    public function handle(MerkenService $merken): int
    {
        $code = $this->argument('merk');
        if ($code && ! $merken->merk($code)) {
            $this->error("Onbekend merk: {$code}. Bekend: ".implode(', ', array_keys($merken->merken())));

            return self::FAILURE;
        }

        $ophalingen = $code ? [$merken->haalOp($code)] : $merken->haalAllesOp();

        foreach ($ophalingen as $o) {
            $regel = "{$o->merk}: {$o->status} — {$o->melding}";
            $o->status === 'fout' ? $this->warn($regel) : $this->info($regel);
        }

        return self::SUCCESS;
    }
}
