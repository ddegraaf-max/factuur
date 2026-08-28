<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

/**
 * Beveiligt de demo-omgeving.
 *
 * In de demo werkt de hele app echt — je maakt facturen aan, registreert
 * betalingen, past instellingen aan — maar niets verlaat het systeem:
 *
 *  - alle uitgaande e-mail gaat naar het logboek in plaats van naar echte
 *    ontvangers (factuurmail, herinneringen, incassodossier);
 *  - betaalroutes (Stripe) en tweestapsverificatie zijn geblokkeerd.
 */
class DemoMode
{
    /** Routes die in de demo geen zin hebben of echt geld/beveiliging raken. */
    protected array $blocked = [
        'billing.checkout' => 'Afrekenen kan niet in de demo. Maak een gratis proefaccount aan om een abonnement af te sluiten.',
        'billing.portal' => 'Het betaalportaal is niet beschikbaar in de demo.',
        'bank.ponto.connect' => 'In de demo kun je geen echte bank koppelen. Maak een gratis proefaccount aan om dit te proberen.',
        'settings.security.setup' => 'Tweestapsverificatie instellen kan in je eigen omgeving.',
        'settings.security.verify' => 'Tweestapsverificatie instellen kan in je eigen omgeving.',
        'settings.security.disable' => 'Tweestapsverificatie instellen kan in je eigen omgeving.',
        'settings.security.recovery' => 'Tweestapsverificatie instellen kan in je eigen omgeving.',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->company?->is_demo) {
            return $next($request);
        }

        // Geen enkele e-mail mag de demo verlaten.
        Config::set('mail.default', 'log');

        $routeName = $request->route()?->getName();
        if ($routeName && isset($this->blocked[$routeName])) {
            return back()->with('error', $this->blocked[$routeName]);
        }

        return $next($request);
    }
}
