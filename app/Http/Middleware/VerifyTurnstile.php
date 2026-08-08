<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifieert de Cloudflare Turnstile-token server-side.
 *
 * - Is er geen secret geconfigureerd (TURNSTILE_SECRET leeg)? Dan slaat de
 *   middleware de controle over, zodat de app blijft werken vóór je de keys
 *   hebt ingesteld. Zodra de secret is ingevuld, wordt elke request gecheckt.
 */
class VerifyTurnstile
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.turnstile.secret');

        // Nog niet geconfigureerd → niet blokkeren.
        if (empty($secret)) {
            return $next($request);
        }

        $token = $request->input('cf-turnstile-response');
        $ok = false;

        if (! empty($token)) {
            try {
                $response = Http::asForm()
                    ->timeout(8)
                    ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                        'secret' => $secret,
                        'response' => $token,
                        'remoteip' => $request->ip(),
                    ]);

                $ok = (bool) ($response->json('success') ?? false);
            } catch (\Throwable $e) {
                Log::warning('Turnstile-verificatie mislukt', ['error' => $e->getMessage()]);
                $ok = false;
            }
        }

        if (! $ok) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'cf-turnstile-response']))
                ->withErrors(['cf-turnstile-response' => 'Bevestig even dat je geen robot bent en probeer opnieuw.']);
        }

        return $next($request);
    }
}
