<?php

namespace App\Console\Commands;

use App\Models\PontoConnection;
use App\Services\Ponto\PontoService;
use App\Services\Ponto\PontoSyncer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PontoSync extends Command
{
    protected $signature = 'ponto:sync';

    protected $description = 'Haal nieuwe banktransacties op voor alle administraties met een actieve Ponto-koppeling.';

    public function handle(PontoService $ponto, PontoSyncer $syncer): int
    {
        if (! $ponto->available()) {
            $this->warn('Ponto niet ingericht (PONTO_* ontbreekt) — niets te doen.');

            return self::SUCCESS;
        }

        $connections = PontoConnection::where('status', PontoConnection::STATUS_ACTIVE)->get();
        foreach ($connections as $connection) {
            // Proef/abonnement verlopen: niet synchroniseren (kost alleen maar Ponto-tegoed).
            if (! $connection->company?->hasAccess()) {
                $this->line("Administratie {$connection->company_id}: overgeslagen (geen actieve toegang).");
                continue;
            }
            try {
                $imported = $syncer->sync($connection);
                $this->info("Administratie {$connection->company_id}: {$imported} nieuwe transactie(s).");
            } catch (\Throwable $e) {
                $this->error("Administratie {$connection->company_id}: {$e->getMessage()}");
                Log::warning('Ponto: synchronisatie mislukt', ['company' => $connection->company_id, 'error' => $e->getMessage()]);
            }
        }
        $this->info("Klaar: {$connections->count()} koppeling(en) verwerkt.");

        return self::SUCCESS;
    }
}
