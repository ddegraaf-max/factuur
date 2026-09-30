<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blokkeert toegang tot de app wanneer er geen actieve administratie is, of
 * wanneer de proefperiode is verlopen en er geen abonnement is. Gebruikers
 * worden dan naar de administraties- of abonnementspagina geleid.
 */
class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        /*
         * Geen actieve administratie? Dan komt er niets van de app langs.
         *
         * ── Waarom dit hier staat ─────────────────────────────────────────
         *
         * users.company_id is nullable met nullOnDelete: verdwijnt een
         * administratie, dan houdt de gebruiker zijn rol (standaard 'owner')
         * maar staat company_id op NULL. En de bedrijfsfilter in elk model luidt
         * "if (auth()->check() && auth()->user()->company_id)" — bij NULL wordt
         * er dus niet gefilterd, en ziet zo iemand de gegevens van álle
         * administraties. Op de grootboekpagina's zijn dat de journaalposten van
         * iedereen, met klantnamen en bedragen erin.
         *
         * Het filteren in de modellen laten we zoals het is; dat is het patroon
         * van de hele applicatie en dat ga je niet op vijftig plekken
         * tegelijk omgooien. De deur gaat hier dicht, op de ene plek waar alle
         * administratiegegevens langskomen.
         *
         * /administraties, /abonnement en uitloggen vallen buiten deze groep,
         * dus wie hier wordt weggestuurd kan een administratie kiezen of
         * aanmaken.
         */
        if ($user && ! $user->company_id) {
            return redirect()->route('administrations.index')
                ->with('error', 'Je hebt geen actieve administratie. Kies er een of maak een nieuwe aan.');
        }

        $company = $user?->company;

        if ($company && ! $company->hasAccess()) {
            return redirect()->route('billing.show')
                ->with('error', 'Je proefperiode is verlopen. Sluit een abonnement af om verder te gaan.');
        }

        return $next($request);
    }
}
