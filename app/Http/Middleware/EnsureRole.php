<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autorisatie per rol: `role:owner` of `role:owner,accountant`.
 *
 * Rollen:
 *  - owner      → Beheerder: alles, incl. instellingen, abonnement en team.
 *  - staff      → Medewerker: dagelijks werk (verkoop en inkoop), geen
 *                 instellingen, rapporten of abonnement.
 *  - accountant → Boekhouder: alles inzien + rapporten/exports, niets wijzigen
 *                 (dat laatste dwingt AccountantReadOnly af).
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            $message = 'Je account heeft geen toegang tot dit onderdeel. Vraag de beheerder om je rol aan te passen.';

            return $request->isMethod('GET')
                ? redirect()->route('dashboard')->with('error', $message)
                : back()->with('error', $message);
        }

        return $next($request);
    }
}
