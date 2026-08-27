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
        // www ↔ zonder-www: één canonieke host (SEO), vóór alle andere middleware.
        $middleware->web(prepend: [RedirectToCanonicalHost::class]);

        $middleware->web(append: [
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
            'subscribed' => EnsureSubscriptionActive::class,
            'turnstile' => VerifyTurnstile::class,
            'portal.verified' => EnsurePortalVerified::class,
            'role' => EnsureRole::class,
            'readonly' => AccountantReadOnly::class,
        ]);

        // Webhooks van buitenaf sturen geen CSRF-token mee.
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'webhooks/mollie',
            'webhooks/inbound-mail/*',
            'mcp/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
