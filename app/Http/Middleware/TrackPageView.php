<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Telt bezoeken aan de publieke marketingpagina's — first-party en
 * privacyvriendelijk (geen cookies, geen IP-opslag), dus zonder cookiebanner.
 *
 * - Alleen GET-verzoeken op de allowlist hieronder; ingelogde gebruikers en
 *   bots die zich als bot melden tellen niet mee. Robots met een gewone
 *   browsernaam komen wel binnen; een bezoek telt pas als mens na het seintje
 *   uit de browser (PageView::confirm, layouts/pageview-signal.blade.php).
 * - Unieke bezoekers per dag via een hash van IP + user-agent + dag + app-key.
 *   De hash is elke dag anders en is niet terug te rekenen naar een persoon.
 * - Schrijft pas ná de response (terminate), zodat de bezoeker er niets van
 *   merkt; fouten worden alleen gelogd.
 */
class TrackPageView
{
    /** Exacte paden die we meten. */
    private const PATHS = [
        '/', '/demo', '/login', '/register', '/contact', '/over-ons',
        '/veelgestelde-vragen', '/wat-is-nieuw', '/roadmap', '/status',
        '/privacy', '/voorwaarden', '/cookies', '/helpcentrum', '/kennisbank',
        '/gratis-factuur-maken', '/btw-calculator', '/uurtarief-calculator',
        '/facturatie-met-ai', '/boekhouders', '/incassokosten-berekenen', '/factuurprogramma-bouw', '/online-aanmaning', '/online-aanmaning/voorbeeld',
    ];

    /** Padprefixen die we meten (artikel- en overstappagina's). */
    private const PREFIXES = ['/helpcentrum/', '/kennisbank/', '/overstappen-van/'];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            if (! $this->shouldTrack($request, $response)) {
                return;
            }

            $referrerHost = null;
            if ($referrer = $request->headers->get('referer')) {
                $host = parse_url($referrer, PHP_URL_HOST) ?: null;
                // Alleen externe herkomst is interessant; eigen site niet.
                if ($host && ! str_contains($host, (string) $request->getHost())) {
                    $referrerHost = mb_substr($host, 0, 190);
                }
            }

            $userAgent = (string) $request->userAgent();

            PageView::create([
                'viewed_on' => now()->toDateString(),
                'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 190),
                'referrer_host' => $referrerHost,
                'utm_source' => $this->utm($request, 'utm_source'),
                'utm_medium' => $this->utm($request, 'utm_medium'),
                'utm_campaign' => $this->utm($request, 'utm_campaign'),
                'device' => preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent) ? 'mobile' : 'desktop',
                'visitor_hash' => PageView::visitorHash($request),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Pageview niet geregistreerd', ['error' => $e->getMessage()]);
        }
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200 || $request->user()) {
            return false;
        }

        $userAgent = (string) $request->userAgent();
        if ($userAgent === '' || preg_match('/bot|crawl|spider|slurp|preview|monitor|pingdom|lighthouse|headless|scrape|curl|wget|python|http/i', $userAgent)) {
            return false;
        }

        $path = '/'.ltrim($request->path(), '/');
        if (in_array($path, self::PATHS, true)) {
            return true;
        }
        foreach (self::PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function utm(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && $value !== '' ? mb_substr($value, 0, 80) : null;
    }
}
