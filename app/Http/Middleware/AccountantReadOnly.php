<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * De boekhouder-rol is alleen-lezen: elke pagina mag bekeken worden, maar
 * niets mag worden aangemaakt, gewijzigd of verwijderd. Deze middleware
 * blokkeert daarom alle niet-GET-verzoeken voor accountants — met een korte
 * lijst uitzonderingen voor dingen die over het eigen account gaan.
 */
class AccountantReadOnly
{
    /** Routes die ook een boekhouder mag gebruiken (eigen account/sessie). */
    protected array $allowed = [
        'logout',
        'demo.stop',
        'settings.security.setup',
        'settings.security.verify',
        'settings.security.disable',
        'settings.security.recovery',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAccountant() && ! $request->isMethodSafe()) {
            $routeName = $request->route()?->getName();

            if (! in_array($routeName, $this->allowed, true)) {
                return back()->with('error', 'Je bent ingelogd als boekhouder: je kunt alles inzien en exporteren, maar niets wijzigen.');
            }
        }

        return $next($request);
    }
}
