<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * De beveiligingskoppen.
 *
 * ── Waarom deze er zijn ───────────────────────────────────────────────────
 *
 * Er stond er geen enkele. Geen HSTS, geen X-Frame-Options, geen
 * X-Content-Type-Options. Dat betekende drie dingen: wie het adres intikt gaat
 * eerst over http, de applicatie is in een iframe te zetten, en een browser mag
 * zelf raden wat voor soort bestand hij binnenkrijgt.
 *
 * ── Waarom hier geen Content-Security-Policy staat ────────────────────────
 *
 * Die hoort erbij, maar niet in dezelfde stap. Een CSP die te streng staat
 * breekt de applicatie pas in de browser van een bezoeker — Vite laadt zijn
 * eigen scripts, Stripe en Turnstile laden die van hen, en de Inertia-pagina
 * draagt zijn gegevens in een attribuut mee. Dat is uit te zoeken, maar het
 * vraagt om meekijken in een echte browser en niet om een gok in één commit
 * samen met vier koppen die niets kunnen breken.
 *
 * ── Waarom HSTS geen preload krijgt ───────────────────────────────────────
 *
 * `preload` zet het domein op een lijst die in de browser zelf is ingebakken,
 * en daar kom je niet zomaar meer af. Dat wil je pas aanzetten als je zeker
 * weet dat élk subdomein voorgoed over https gaat — en dat is een beslissing
 * van de eigenaar, niet van een middleware.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Een jaar, inclusief subdomeinen. Zonder dit gaat de eerste aanvraag van
        // een bezoeker die het adres intikt over http, en pas de omleiding daarna
        // over https — precies het moment waarop meekijken nog loont.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Niet in een iframe. Er is geen enkele reden om deze applicatie in te
        // sluiten, en zonder deze kop is een klik op de verkeerde plek te sturen.
        $response->headers->set('X-Frame-Options', 'DENY');

        // Geen eigen gok over het soort bestand. Een geüpload bestand dat als
        // afbeelding is bedoeld mag nooit als script worden uitgevoerd.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Wel het domein meesturen naar andere sites, niet het volledige pad:
        // in een adres als /facturen/9123 staat informatie die de ontvanger
        // niets aangaat.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Camera, microfoon en locatie heeft deze applicatie niet nodig. Staat
        // dit niet uit, dan mag een ingesloten derde partij er alsnog om vragen.
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');

        return $response;
    }
}
