<?php

namespace App\Http\Middleware;

use App\Support\Market;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Taal van de gebruikersinterface. De markt bepaalt de standaardtaal (nl in
 * Nederland, pl in Polen) én welke talen te kiezen zijn ('locales' in
 * config/markets.php); Lopra Polska biedt Pools en Engels. Volgorde:
 * ?lang=… in de URL (wordt onthouden), dan de voorkeur van de ingelogde
 * gebruiker, dan de sessie, anders de markttaal. Klantdocumenten (PDF's,
 * klantmails) volgen hun eigen taal via App\Support\DocumentLocale.
 */
class SetLocale
{
    public const SESSION_KEY = 'ui_locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = self::resolve($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    public static function resolve(Request $request): string
    {
        $allowed = Market::uiLocales();
        $default = Market::locale();

        if (count($allowed) < 2) {
            return $default;
        }

        $fromQuery = $request->query('lang');
        if (is_string($fromQuery) && in_array($fromQuery, $allowed, true)) {
            self::remember($request, $fromQuery);

            return $fromQuery;
        }

        $fromUser = $request->user()?->locale;
        if ($fromUser && in_array($fromUser, $allowed, true)) {
            return $fromUser;
        }

        $fromSession = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;
        if ($fromSession && in_array($fromSession, $allowed, true)) {
            return $fromSession;
        }

        return $default;
    }

    /** Onthoud de keuze in de sessie en — indien ingelogd — bij de gebruiker. */
    public static function remember(Request $request, string $locale): void
    {
        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $locale);
        }

        $user = $request->user();
        if ($user && $user->locale !== $locale) {
            $user->forceFill(['locale' => $locale])->save();
        }
    }
}
