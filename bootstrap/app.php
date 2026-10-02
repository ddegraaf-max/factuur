<?php

use App\Http\Middleware\AccountantReadOnly;
use App\Http\Middleware\DemoMode;
use App\Http\Middleware\EnsurePortalVerified;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\TrackPageView;
use App\Http\Middleware\VerifyTurnstile;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectToCanonicalHost;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /*
         * De proxy van het platform vertrouwen.
         *
         * Railway zet TLS af vóór de applicatie en geeft het verzoek intern door
         * over http, met de werkelijke herkomst in X-Forwarded-Proto. Zonder deze
         * regel negeert Laravel die kop en denkt het dat alles onversleuteld
         * binnenkomt.
         *
         * Dat is geen theorie: een registratie die op de validatie strandde kreeg
         * een 302 naar http://easybookkeeper.nl/register. De redirect naar https
         * ving dat op, dus het wérkte — maar elke formulierfout kostte een extra
         * rondje en de bezoeker stond daarbij even op een onversleuteld adres.
         *
         * Het had erger gekund. Gebruikte de bevestiging van een e-mailadres een
         * ondertekende link in plaats van een code, dan was de handtekening op
         * http gezet en op https ongeldig geweest — en had niemand zijn adres
         * kunnen bevestigen.
         *
         * '*' en niet een lijst met adressen: op Railway staat er een laag vóór
         * ons waarvan het adres niet vastligt en zonder aankondiging verandert.
         * Een lijst die stilletjes veroudert geeft precies dezelfde storing terug,
         * alleen later en moeilijker te vinden. Het verkeer bereikt ons uitsluitend
         * via die laag.
         */
        $middleware->trustProxies(at: '*');

        /*
         * De beveiligingskoppen, op élk antwoord.
         *
         * Globaal en niet op de web-groep alleen: nosniff hoort juist op de
         * antwoorden die géén pagina zijn — een json-fout, een download, een
         * bestand uit de opslag. Dat zijn precies de plekken waar een browser
         * zelf gaat raden wat hij binnenkrijgt.
         */
        $middleware->append(SecurityHeaders::class);

        // www ↔ zonder-www: één canonieke host (SEO), vóór alle andere middleware.
        $middleware->web(prepend: [RedirectToCanonicalHost::class]);

        $middleware->web(append: [
            // Interfacetaal (markttaal, of de PL/EN-keuze van de bezoeker/gebruiker).
            \App\Http\Middleware\SetLocale::class,
            // Vóór Inertia: zet de demo-omgeving in veilige modus (geen echte
            // e-mail, geen betaalroutes) voordat er iets wordt afgehandeld.
            DemoMode::class,
            HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            // Bezoekersstatistieken voor de marketingpagina's (schrijft pas
            // ná de response; zie de middleware zelf voor de allowlist).
            TrackPageView::class,
        ]);

        $middleware->alias([
            'owner' => \App\Http\Middleware\EnsureOwner::class,
            'market' => \App\Http\Middleware\EnsureMarket::class,
            'subscribed' => EnsureSubscriptionActive::class,
            'turnstile' => VerifyTurnstile::class,
            'portal.verified' => EnsurePortalVerified::class,
            'role' => EnsureRole::class,
            'readonly' => AccountantReadOnly::class,
        ]);

        // Webhooks van buitenaf sturen geen CSRF-token mee.
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'webhooks/smstools',
            'webhooks/mollie',
            'webhooks/recommand',
            'webhooks/inbound-mail/*',
            'mcp/*',
            // Seintje van de bezoekersteller (sendBeacon kan geen token meesturen).
            'm/gezien',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Foutbewaking: onverwachte fouten per mail naar de eigenaar (gedoseerd).
        $exceptions->report(fn (\Throwable $e) => \App\Support\ErrorAlert::report($e));
    })->create();
