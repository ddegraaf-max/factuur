<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Eén domeinvariant: www.easyinvoice.nl en easyinvoice.nl zijn hetzelfde,
 * maar zoekmachines zien twee sites (dubbele titels, verdeelde linkwaarde).
 * Alles wat niet op de host van APP_URL binnenkomt, krijgt een 301 naar
 * dezelfde URL op de canonieke host. Alleen voor GET/HEAD — een POST mag
 * nooit stilletjes van host wisselen.
 */
class RedirectToCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonical = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $host = strtolower($request->getHost());

        if ($canonical !== '' && $host !== $canonical && in_array($request->method(), ['GET', 'HEAD'], true)) {
            $bare = preg_replace('/^www\./', '', $host);
            $canonicalBare = preg_replace('/^www\./', '', $canonical);

            // Alleen www ↔ zonder-www omleiden; andere hosts (localhost, preview-URL's) met rust laten.
            if ($bare === $canonicalBare) {
                return redirect()->to($request->getScheme() . '://' . $canonical . $request->getRequestUri(), 301);
            }
        }

        return $next($request);
    }
}
