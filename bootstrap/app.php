<?php

use App\Http\Middleware\DemoMode;
use App\Http\Middleware\EnsurePortalVerified;
use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\VerifyTurnstile;
use App\Http\Middleware\HandleInertiaRequests;
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
        $middleware->web(append: [
            // Vóór Inertia: zet de demo-omgeving in veilige modus (geen echte
            // e-mail, geen betaalroutes) voordat er iets wordt afgehandeld.
            DemoMode::class,
            HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'subscribed' => EnsureSubscriptionActive::class,
            'turnstile' => VerifyTurnstile::class,
            'portal.verified' => EnsurePortalVerified::class,
        ]);

        // Stripe-webhook stuurt geen CSRF-token mee.
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
