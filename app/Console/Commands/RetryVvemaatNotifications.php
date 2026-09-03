<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\VvemaatService;
use Illuminate\Console\Command;

/**
 * Meldingen aan VvEMaat die niet zijn aangekomen alsnog versturen.
 *
 * De melding gaat mee op het moment dat een betaling wordt vastgelegd. Dat is
 * bewust direct en niet via de wachtrij: in deze omgeving draait geen
 * queue-worker, dus een job zou blijven staan zonder dat iemand het merkt.
 *
 * Direct versturen betekent wel dat een storing aan de andere kant de melding
 * kost. En dat is geen kleinigheid: aan die melding hangt of een vereniging
 * haar administratie kan bijwerken. Vandaar dit vangnet — het loopt langs de
 * facturen die betaald zijn maar nog niet gemeld, en probeert het opnieuw.
 */
class RetryVvemaatNotifications extends Command
{
    protected $signature = 'vvemaat:meld-betalingen';

    protected $description = 'Verstuurt betaalmeldingen aan VvEMaat die eerder niet aankwamen.';

    public function handle(VvemaatService $vvemaat): int
    {
        if (! $vvemaat->actief()) {
            $this->info('Koppeling met VvEMaat staat uit; niets te doen.');

            return self::SUCCESS;
        }

        /*
         * Alleen facturen van klanten met een VvE-omgeving, betaald, nog niet
         * gemeld, en met een periode erop. Zonder periode weten we niet tot
         * wanneer er toegang is, en dan is niets sturen beter dan gokken.
         *
         * Ver terugkijken heeft geen zin: staat een melding een maand later nog
         * open, dan is er iets anders aan de hand dan een storing van een uur.
         */
        $facturen = Invoice::withoutGlobalScope('company')
            ->where('status', 'paid')
            ->whereNull('vvemaat_notified_at')
            ->whereNotNull('period_end')
            ->where('paid_at', '>=', now()->subDays(30))
            ->whereHas('customer', fn ($q) => $q->withoutGlobalScope('company')
                ->whereNotNull('vvemaat_slug')->where('vvemaat_slug', '<>', ''))
            ->with('customer')
            ->orderBy('paid_at')
            ->limit(200)
            ->get();

        $gelukt = 0;
        foreach ($facturen as $factuur) {
            if ($vvemaat->meldBetaling($factuur)) {
                $gelukt++;
            }
        }

        $this->info("Gemeld: {$gelukt} van {$facturen->count()} openstaande melding(en).");

        return self::SUCCESS;
    }
}
