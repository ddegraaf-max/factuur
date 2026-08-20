<?php

namespace App\Console\Commands;

use App\Models\PurchaseInboxItem;
use App\Services\ReceiptScanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Postvak IN automatisch verwerken: herken nieuw aangeleverde bestanden met
 * de bestaande AI-scan en bewaar het resultaat als boekingsvoorstel op het
 * item. Draait elke vijf minuten (zie routes/console.php) — tegen de tijd
 * dat de gebruiker het postvak opent, ligt het voorstel klaar.
 *
 * Zonder ANTHROPIC_API_KEY doet dit commando niets; het handmatige
 * "Inboeken" (met scanknop) blijft altijd werken.
 */
class ScanPurchaseInbox extends Command
{
    protected $signature = 'purchases:scan-inbox {--limit=10 : Maximaal aantal items per run}';

    protected $description = 'Herken nieuwe Postvak IN-items automatisch (scan & herken)';

    public function handle(ReceiptScanService $scanner): int
    {
        if (! $scanner->enabled()) {
            $this->info('Bonherkenning is niet geconfigureerd — niets te doen.');

            return self::SUCCESS;
        }

        // Demo-omgevingen slaan we over: die mogen geen AI-kosten maken.
        // Alleen administraties met AI-toegang (Slim, proef of vrijgesteld)
        // krijgen automatische boekingsvoorstellen.
        $items = PurchaseInboxItem::query()
            ->with('company')
            ->where('status', 'pending')
            ->whereNull('scanned_at')
            ->whereHas('company', fn ($q) => $q->where('is_demo', false))
            ->orderBy('received_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->get()
            ->filter(fn ($item) => $item->company?->hasAiAccess());

        $done = 0;
        foreach ($items as $item) {
            // Altijd afstempelen — ook bij een fout — zodat een kapot bestand
            // niet elke vijf minuten opnieuw wordt geprobeerd.
            $update = ['scanned_at' => now()];

            try {
                $contents = $item->contents();
                if ($contents === null) {
                    throw new \DomainException('Het bestand kon niet worden gelezen.');
                }
                $update['scan'] = $scanner->scan($contents, $item->mime_type);
                $done++;
            } catch (\DomainException $e) {
                $update['scan_error'] = mb_substr($e->getMessage(), 0, 300);
            } catch (\Throwable $e) {
                Log::warning('Postvak-scan mislukt', ['item' => $item->id, 'error' => $e->getMessage()]);
                $update['scan_error'] = 'Het bestand kon niet automatisch worden herkend.';
            }

            $item->forceFill($update)->saveQuietly();
        }

        $this->info("Gescand: {$done} van {$items->count()} item(s).");

        return self::SUCCESS;
    }
}
