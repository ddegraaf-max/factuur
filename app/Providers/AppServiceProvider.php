<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Globale helpers (brand() …) — hier geladen zodat er geen composer dump-autoload nodig is.
        require_once app_path('Support/helpers.php');
    }

    public function boot(): void
    {
        // Forceer HTTPS in productie (nodig achter proxy's zoals Railway)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        // Logboek: aanmaken/wijzigen/verwijderen van de kernmodellen, plus in- en uitloggen.
        foreach ([
            \App\Models\Invoice::class, \App\Models\Quote::class, \App\Models\Customer::class, \App\Models\Product::class,
            \App\Models\Payment::class, \App\Models\PurchaseInvoice::class, \App\Models\Company::class,
            \App\Models\RecurringInvoice::class, \App\Models\BrandProfile::class,
        ] as $model) {
            $model::observe(\App\Observers\ActivityObserver::class);
        }
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            \App\Support\Audit::log('login', null, ($event->user->name ?? 'Gebruiker') . ' ingelogd', [], $event->user->company_id ?? null);
        });
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) {
            if ($event->user) {
                \App\Support\Audit::log('logout', null, ($event->user->name ?? 'Gebruiker') . ' uitgelogd', [], $event->user->company_id ?? null);
            }
        });
    }
}
