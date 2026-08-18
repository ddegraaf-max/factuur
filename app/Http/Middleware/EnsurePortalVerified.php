<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Portal\PortalAuthController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Beschermt portaalpagina's: alleen toegankelijk met een sessie waarin het
 * e-mailadres via een code is geverifieerd (en die niet is verlopen).
 */
class EnsurePortalVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! PortalAuthController::verifiedEmail($request)) {
            $request->session()->put('portal_intended', $request->fullUrl());

            return redirect()->route('portal.login');
        }

        return $next($request);
    }
}
