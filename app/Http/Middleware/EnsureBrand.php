<?php

namespace App\Http\Middleware;

use App\Support\Brand;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pagina's die maar bij één merk horen (de Fachora-marktplaats, de Lopra-
 * kalkulator): onder een ander merk op dezelfde markt geven ze 404. Zo
 * serveert fachora.pl geen Lopra-pagina's en andersom.
 *
 * Gebruik: ->middleware('brand:fachora') of ->middleware('brand:lopra_pl,lopra').
 */
class EnsureBrand
{
    public function handle(Request $request, Closure $next, string ...$brands): Response
    {
        abort_unless(in_array(Brand::key(), $brands, true), 404);

        return $next($request);
    }
}
