<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blokkeert toegang tot de app wanneer de proefperiode is verlopen en er geen
 * actief abonnement is. Gebruikers worden dan naar de abonnementspagina geleid.
 */
class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->user()?->company;

        if ($company && ! $company->hasAccess()) {
            return redirect()->route('billing.show')
                ->with('error', 'Je proefperiode is verlopen. Sluit een abonnement af om verder te gaan.');
        }

        return $next($request);
    }
}
