<?php

namespace App\Http\Middleware;

use App\Support\Market;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pagina's die maar in één markt bestaan (Nederlandse kennisbank, Poolse
 * kalkulator odsetek …): buiten die markt geven ze 404, zodat de Poolse site
 * geen Nederlandse marketingpagina's serveert en andersom.
 *
 * Gebruik: ->middleware('market:nl') of ->middleware('market:pl').
 */
class EnsureMarket
{
    public function handle(Request $request, Closure $next, string ...$markets): Response
    {
        abort_unless(in_array(Market::key(), $markets, true), 404);

        return $next($request);
    }
}
