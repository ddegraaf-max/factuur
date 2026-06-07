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
            'auth' => [
                'user' => $request->user(),
                'company' => $request->user()?->company,
            ],
            'flash' => fn () => [
                'flash' => $request->session()->get('flash'),
                'error' => $request->session()->get('error'),
            ],
            'ziggy' => fn () => [
                'location' => $request->url(),
            ],
        ]);

        // Share EASY insights data only when authenticated (lazy load)
        if ($request->user()) {
            $shared['easy_insights'] = fn () => app(EasyInsightsService::class)->gather();
            $shared['easy_data'] = fn () => app(EasyInsightsService::class)->data();
            $shared['subscription'] = fn () => $request->user()->company?->subscriptionSummary();
        }

        return $shared;
    }
}
