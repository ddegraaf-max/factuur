<?php

namespace App\Console\Commands;

use App\Models\Provider;
use App\Services\Fachora\LetterService;
use App\Services\Fachora\PingenClient;
use App\Services\Fachora\RegisterNoticeService;
use App\Support\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 1.109: brieven per post aan bedrijven die een klant uit de lijst koos maar nog niet geclaimd
 * zijn. Post is de enige weg zonder toestemming voor elektronische berichten (ConsentPolicy).
 * Pilot in één stad (config fachora.letters.city), hoogstens per_day per run, nooit twee keer
 * aan hetzelfde bedrijf. Zonder Pingen-gegevens of met dry_run: alleen tonen wat er zou gaan.
 */
class FachoraLetters extends Command
{
    protected $signature = 'fachora:letters
        {--city= : alleen deze stad (standaard config fachora.letters.city)}
        {--limit= : hoogstens zoveel brieven (standaard fachora.letters.per_day)}
        {--all : ook bedrijven die (nog) geen klant koos}
        {--dry-run : niets versturen, alleen tonen}';

    protected $description = 'Brieven per post (Pingen) aan niet-geclaimde bedrijven die een klant koos — pilot per stad';

    public function handle(LetterService $letters, PingenClient $pingen): int
    {
        if (! Brand::is('fachora')) {
            return self::SUCCESS;
        }
        $cfg = (array) config('fachora.letters', []);
        $fromScheduler = ! $this->option('city') && ! $this->option('limit') && ! $this->option('all') && ! $this->option('dry-run');
        if ($fromScheduler && ! ($cfg['enabled'] ?? false)) {
            return self::SUCCESS; // uit zolang FACHORA_LETTERS niet aanstaat
        }

        $city = trim((string) ($this->option('city') ?: ($cfg['city'] ?? ''))) ?: null;
        $limit = max(1, (int) ($this->option('limit') ?: ($cfg['per_day'] ?? 10)));
        $dry = (bool) $this->option('dry-run') || ! empty($cfg['dry_run']) || ! $pingen->configured();

        $candidates = $letters->candidates(null, $city, $limit * 5, chosenOnly: ! $this->option('all'))
            ->filter(fn (Provider $p) => $p->letter_sent_at === null && $p->removed_at === null && $p->claimed_at === null && $p->email_blocked_at === null)
            ->take($limit)
            ->values();

        if ($candidates->isEmpty()) {
            $this->info('Geen bedrijven voor een brief' . ($city ? " in {$city}" : '') . '.');
            Log::info('fachora:letters: geen bedrijven voor een brief', ['stad' => $city, 'proef' => $dry]);

            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($candidates as $provider) {
            $label = sprintf('%s · %s, %s %s', $provider->display_name, $provider->address_line, $provider->postal_code, $provider->city);
            if ($dry) {
                $this->line('[proef] ' . $label);
                continue;
            }
            try {
                $pdf = $letters->pdf(collect([$provider]), chosen: true, postal: true);
                $ref = $pingen->sendLetter($pdf, 'fachora-' . $provider->id . '.pdf');
                $provider->forceFill(['letter_sent_at' => now(), 'letter_ref' => $ref])->saveQuietly();
                $sent++;
                $this->line('verstuurd: ' . $label . ' (' . $ref . ')');
            } catch (\Throwable $e) {
                Log::error('Brief via Pingen mislukt', ['provider' => $provider->id, 'error' => $e->getMessage()]);
                $this->error('mislukt: ' . $label . ' — ' . $e->getMessage());
            }
        }

        if ($sent > 0) {
            RegisterNoticeService::log('letter_sent', $sent, 'brieven per post via Pingen' . ($city ? ", stad {$city}" : '') . ' aan bedrijven die een klant koos');
        }
        // De planner stuurt de console-uitvoer naar /dev/null; het logboek van Railway laat zo toch zien wat er gebeurde.
        Log::info('fachora:letters: run klaar', ['stad' => $city, 'proef' => $dry, 'kandidaten' => $candidates->count(), 'verstuurd' => $sent, 'bedrijven' => $candidates->pluck('id')->all()]);
        $this->info($dry
            ? sprintf('Proefdraai: %d brieven zouden worden verstuurd%s.', $candidates->count(), $pingen->configured() ? '' : ' (Pingen niet ingesteld)')
            : sprintf('%d van %d brieven verstuurd.', $sent, $candidates->count()));

        return self::SUCCESS;
    }
}
