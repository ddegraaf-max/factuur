<?php

namespace App\Http\Middleware;

use App\Support\Brand;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Een merk kan een beperkt portaal hebben (config/brand.php 'portal.hidden'):
 * routes waarvan de naam met een van die voorvoegsels begint, bestaan onder dat
 * merk niet (404). Zo ziet een Pools bedrijf op Fachora geen Nederlandse
 * onderdelen (ritten, automatische incasso, grootboek, bankkoppeling …), ook
 * niet via een ingetikt adres. Het menu volgt dezelfde lijst (AppLayout.vue).
 */
class PortalScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $hidden = (array) Brand::get('portal.hidden', []);
        $name = (string) ($request->route()?->getName() ?? '');

        if ($hidden !== [] && $name !== '') {
            foreach ($hidden as $prefix) {
                if (str_starts_with($name, (string) $prefix)) {
                    abort(404);
                }
            }
        }

        return $next($request);
    }
}
