<?php

namespace App\Services\Fachora;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Builder;

/**
 * Informatieplicht art. 14 AVG voor bedrijven uit het register (1.87).
 *
 * Wie gegevens uit CEIDG opslaat, moet de ondernemer binnen een maand laten
 * weten dat, waarom en hoe lang; ook als de gegevens openbaar zijn (UODO tegen
 * Bisnode, NSA 19 september 2023, III OSK 2538/21). Daarom krijgt elk
 * geïmporteerd bedrijf met een e-mailadres één zakelijke informatiemail,
 * zonder reclame, met de keuze: geen e-mail meer, of het profiel verwijderen.
 * In kleine porties, zodat het niet als bulkmail oogt.
 */
class RegisterNoticeService
{
    public function __construct(private Notifier $notifier) {}

    public function enabled(): bool
    {
        return (bool) config('fachora.register_notices', false);
    }

    /** Bedrijven die de informatiemail nog moeten krijgen. */
    public function pending(): Builder
    {
        return Provider::query()
            ->whereIn('source', ['ceidg', 'krs', 'import'])
            ->whereNull('claimed_at')->whereNull('register_notice_at')->whereNull('removed_at')
            ->whereNotNull('email')->whereNull('email_blocked_at')
            ->whereNotIn('status', ['blocked']);
    }

    public function backlog(): int
    {
        return $this->pending()->count();
    }

    /** Pauzeert de regio-import, zodat niemand langer dan een maand wacht. */
    public function importShouldPause(): bool
    {
        return $this->enabled() && $this->backlog() > (int) config('fachora.notice_backlog_max', 3000);
    }

    public function sentToday(): int
    {
        return Provider::query()->where('register_notice_at', '>=', now('Europe/Warsaw')->startOfDay()->utc())->count();
    }

    /**
     * Eén portie. De planner draait dit elke 30 minuten op werkdagen tussen 8
     * en 18 uur (20 runs), dus per run ongeveer een twintigste van het dagbudget.
     *
     * @return array{sent: int, failed: int, left_today: int}
     */
    public function sendBatch(?int $max = null): array
    {
        $left = max(0, (int) config('fachora.notices_per_day', 300) - $this->sentToday());
        $max = min($left, $max ?? (int) ceil(config('fachora.notices_per_day', 300) / 20));
        $sent = $failed = 0;
        if (! $this->enabled() || $max <= 0) {
            return ['sent' => 0, 'failed' => 0, 'left_today' => $left];
        }

        foreach ($this->pending()->orderBy('id')->limit($max)->get() as $provider) {
            if ($provider->isDemo()) {
                $provider->forceFill(['register_notice_at' => now()])->save();

                continue;
            }
            if ($this->notifier->registerNotice($provider)) {
                $provider->forceFill(['register_notice_at' => now()])->save();
                $sent++;
            } else {
                $failed++;
            }
            usleep(500_000);
        }

        return ['sent' => $sent, 'failed' => $failed, 'left_today' => $left - $sent];
    }

    /** Een bedrijf dat via een klantaanvraag al de art. 14-informatie kreeg, hoeft niet nog eens. */
    public static function markInformed(Provider $provider): void
    {
        if ($provider->register_notice_at === null) {
            $provider->forceFill(['register_notice_at' => now()])->save();
        }
    }

    /**
     * Bezwaar (art. 21 AVG): profiel weg. Alleen het NIP blijft, zodat een
     * volgende import het bedrijf niet opnieuw aanmaakt (zo staat het in het
     * privacybeleid).
     */
    public static function remove(Provider $provider): void
    {
        if ($provider->claimed_at !== null || $provider->removed_at !== null) {
            return; // een geclaimd profiel verwijdert de eigenaar zelf in het panel
        }
        $provider->forceFill([
            'status' => 'blocked',
            'status_reason' => 'Verwijderd op verzoek (art. 21 AVG)',
            'removed_at' => now(),
            'email_blocked_at' => $provider->email_blocked_at ?? now(),
            'legal_name' => 'Usunięty profil',
            'display_name' => 'Usunięty profil',
            'regon' => null, 'krs' => null, 'address_line' => null, 'postal_code' => null, 'city' => null,
            'voivodeship' => null, 'lat' => null, 'lng' => null, 'phone' => null, 'email' => null, 'website' => null,
            'description' => null, 'photo_data' => null, 'claim_code' => null,
        ])->save();
        $provider->syncCategories([]);
    }

    /**
     * Optie 2 (1.87): bedrijven uit het register die niemand koos en die nooit
     * een bericht van ons kregen, bewaren we niet. Ook geen geschiedenis kwijt:
     * wie een aanbieding of opdracht heeft, blijft staan.
     */
    public function unusedRegister(int $days): Builder
    {
        return Provider::query()
            ->whereIn('source', ['ceidg', 'krs', 'import'])
            ->whereNull('claimed_at')->whereNull('consent_at')->whereNull('terms_accepted_at')
            ->whereNull('register_notice_at')->whereNull('removed_at')->whereNull('user_id')
            ->where('created_at', '<', now()->subDays($days))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('service_job_offers')->whereColumn('service_job_offers.provider_id', 'providers.id'))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('service_jobs')->whereColumn('service_jobs.provider_id', 'providers.id'));
    }

    /** @return int  het aantal verwijderde (of bij $dry: te verwijderen) bedrijven */
    public function purgeUnusedRegister(int $days, bool $dry = false): int
    {
        $query = $this->unusedRegister($days);
        if ($dry) {
            return $query->count();
        }
        $deleted = 0;
        $query->select('id')->chunkById(500, function ($chunk) use (&$deleted) {
            $ids = $chunk->pluck('id')->all();
            \Illuminate\Support\Facades\DB::table('provider_category')->whereIn('provider_id', $ids)->delete();
            $deleted += Provider::query()->whereIn('id', $ids)->delete();
        });

        return $deleted;
    }
}
