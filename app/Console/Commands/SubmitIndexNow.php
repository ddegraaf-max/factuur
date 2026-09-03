<?php

namespace App\Console\Commands;

use App\Services\SitemapService;
use App\Support\Brand;
use App\Support\IndexNow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Alle sitemap-URL's aanmelden bij IndexNow, één keer per uitgebrachte versie.
 *
 * Elke deploy kan inhoud veranderd hebben (artikelen, pagina's, nieuwe
 * websites van administraties), dus na een nieuwe versie melden we de hele
 * lijst opnieuw; daartussen niets, want IndexNow wil alleen wijzigingen.
 * Draait dagelijks via de planner en doet buiten productie niets, tenzij
 * --force (voor tests en handmatig proberen).
 */
class SubmitIndexNow extends Command
{
    protected $signature = 'seo:indexnow {--force : Ook buiten productie versturen}';

    protected $description = 'Meldt alle sitemap-URL\'s aan bij IndexNow (Bing en partners) na een nieuwe versie.';

    public function handle(SitemapService $sitemap): int
    {
        if (! app()->environment('production') && ! $this->option('force')) {
            $this->info('Niet in productie; niets verstuurd (gebruik --force om te proberen).');

            return self::SUCCESS;
        }

        $version = Brand::version();
        $cacheKey = 'indexnow:submitted_version:' . IndexNow::host();
        if (Cache::get($cacheKey) === $version) {
            $this->info("Versie {$version} is al aangemeld; niets te doen.");

            return self::SUCCESS;
        }

        $urls = $sitemap->allUrls();
        if ($urls === []) {
            $this->warn('Geen URL\'s in de sitemap.');

            return self::SUCCESS;
        }

        try {
            $response = Http::timeout(20)->acceptJson()->post(IndexNow::ENDPOINT, [
                'host' => IndexNow::host(),
                'key' => IndexNow::key(),
                'keyLocation' => IndexNow::keyUrl(),
                'urlList' => array_slice($urls, 0, 10000),
            ]);
        } catch (\Throwable $e) {
            Log::warning('IndexNow onbereikbaar', ['fout' => $e->getMessage()]);
            $this->error('IndexNow onbereikbaar: ' . $e->getMessage());

            return self::FAILURE;
        }

        // 200 = verwerkt, 202 = ontvangen (sleutel wordt later gecontroleerd).
        if (! in_array($response->status(), [200, 202], true)) {
            Log::warning('IndexNow wees de aanmelding af', ['status' => $response->status(), 'antwoord' => $response->body()]);
            $this->error("IndexNow antwoordde {$response->status()}: " . $response->body());

            return self::FAILURE;
        }

        Cache::forever($cacheKey, $version);
        Log::info('IndexNow: sitemap aangemeld', ['versie' => $version, 'urls' => count($urls), 'status' => $response->status()]);
        $this->info(count($urls) . " URL's aangemeld bij IndexNow voor versie {$version} (status {$response->status()}).");

        return self::SUCCESS;
    }
}
