<?php

namespace App\Http\Middleware;

use App\Support\OwnerAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Alleen de eigenaar van EasyInvoice (zie OwnerAccess) mag de interne pagina's zien. */
class EnsureOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(OwnerAccess::allows($request->user()), 403);

        return $next($request);
    }
}
