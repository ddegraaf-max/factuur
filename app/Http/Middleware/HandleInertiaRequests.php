<?php

namespace App\Http\Middleware;

use App\Services\EasyInsightsService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $shared = array_merge(parent::share($request), [
            'version' => \App\Support\Brand::version(),
            // Het actieve merk (EasyInvoice of Lopra): naam, logo's, kleuren — zie config/brand.php.
            'brand' => \App\Support\Brand::forClient(),
            // De markt (nl/pl): taal, valuta, btw-tarieven, NIP/KvK-labels — zie config/markets.php.
            'market' => \App\Support\Market::forClient(),
            // Actieve interfacetaal (kan in Polen afwijken van de markttaal: PL/EN).
            'locale' => app()->getLocale(),
            /*
             * De publieke Turnstile-sitesleutel, vanaf de server.
             *
             * Hij stond alleen in VITE_TURNSTILE_SITEKEY, en die wordt bij het
             * bouwen van de frontend ingebakken. Zet je hem daarna als
             * omgevingsvariabele, dan verandert er niets tot er opnieuw wordt
             * gebouwd — terwijl TURNSTILE_SECRET wél meteen werkt. Dat geeft de
             * slechtst denkbare stand: de controle staat aan, het widget wordt
             * niet getekend, en dus wordt élke aanmelding geweigerd.
             *
             * Dat overkwam EasyBookkeeper bij het live zetten. Via de server
             * werkt de sleutel meteen; VITE_TURNSTILE_SITEKEY blijft werken
             * voor wie hem al zo heeft staan.
             */
            'turnstile_sitekey' => (string) config('services.turnstile.sitekey', ''),
            'auth' => [
                'user' => $request->user(),
                'company' => $request->user()?->company,
                // Rechten voor de interface (de routes dwingen dit óók af):
                // beheerder = alles; medewerker = verkoop + inkoop;
                // boekhouder = alleen inzien + rapporten/exports.
                'can' => $request->user() ? [
                    'write' => ! $request->user()->isAccountant(),
                    'reports' => $request->user()->hasRole('owner', 'accountant'),
                    'settings' => $request->user()->isOwner(),
                    'team' => $request->user()->isOwner(),
                    'billing' => $request->user()->isOwner(),
                    // Sms-tegoed: alleen waar sms bestaat (zie SmsService::enabled).
                    'sms' => $request->user()->isOwner() && app(\App\Services\SmsService::class)->enabled($request->user()->company),
                    // Platform-eigenaar (EasyInvoice zelf): marketing-inzichten,
                    // merkbewaking, administraties — zie App\Support\OwnerAccess.
                    'platform' => \App\Support\OwnerAccess::allows($request->user()),
                ] : null,
                'role_label' => $request->user()?->roleLabel(),
                // Alle administraties van deze gebruiker, voor de wisselaar
                // in de zijbalk (klein lijstje: alleen id + naam).
                'administrations' => $request->user()
                    ? $request->user()->companies()->orderBy('name')->get(['companies.id', 'companies.name'])
                        ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
                    : [],
            ],
            'flash' => fn () => [
                'flash' => $request->session()->get('flash'),
                'error' => $request->session()->get('error'),
            ],
            'ziggy' => fn () => [
                'location' => $request->url(),
            ],
        ]);

        // Klantenportaal: het geverifieerde e-mailadres voor de portaal-layout.
        if ($request->routeIs('portal.*')) {
            $shared['portal_email'] = \App\Http\Controllers\Portal\PortalAuthController::verifiedEmail($request);
        }

        // Share EASY insights data only when authenticated (lazy load)
        if ($request->user()) {
            $shared['easy_insights'] = fn () => app(EasyInsightsService::class)->gather();
            $shared['easy_data'] = fn () => app(EasyInsightsService::class)->data();
            $shared['subscription'] = fn () => $request->user()->company?->subscriptionSummary();
            $shared['demo'] = (bool) $request->user()->company?->is_demo;
        }

        return $shared;
    }
}
