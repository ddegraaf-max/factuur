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
            'version' => config('app.version'),
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
                ] : null,
                'role_label' => $request->user()?->roleLabel(),
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
