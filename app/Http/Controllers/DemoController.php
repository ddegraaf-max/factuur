<?php

namespace App\Http\Controllers;

use App\Services\DemoDataBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DemoController extends Controller
{
    /** Introductiepagina met de startknop. */
    public function show(Request $request)
    {
        // Zit je al in een demo? Dan meteen door naar het dashboard.
        if ($request->user()?->company?->is_demo) {
            return redirect()->route('dashboard');
        }

        // Demo-uitleg per markt: Poolse pagina onder Lopra Polska.
        return view(\App\Support\Market::isPl() ? 'lopra-pl.demo' : 'marketing.demo');
    }

    /** Bouw een verse demo-omgeving en log de bezoeker in. */
    public function start(Request $request, DemoDataBuilder $builder): RedirectResponse
    {
        if ($request->user()?->company?->is_demo) {
            return redirect()->route('dashboard');
        }

        // Een ingelogde échte gebruiker mag zijn eigen sessie niet kwijtraken.
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        try {
            $user = $builder->build();
        } catch (\Throwable $e) {
            Log::error('Demo-omgeving aanmaken mislukt', ['error' => $e->getMessage()]);

            return back()->with('error', __('De demo kon even niet worden gestart. Probeer het zo nog eens.'));
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('is_demo', true);

        return redirect()->route('dashboard')
            ->with('flash', __('Welkom in de demo! Alle gegevens zijn voorbeelden — klik gerust overal op.'));
    }

    /**
     * Verlaat de demo: uitloggen en de sandbox direct opruimen.
     * Met ?to=register landt de bezoeker meteen op het registratieformulier —
     * dat lukt pas ná het uitloggen, want die pagina is alleen voor gasten.
     */
    public function stop(Request $request): RedirectResponse
    {
        $company = $request->user()?->company;
        $target = $request->input('to') === 'register' ? 'register' : 'home';

        if ($company?->is_demo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            try {
                app(\App\Services\DemoCleaner::class)->delete($company);
            } catch (\Throwable $e) {
                // Lukt het opruimen nu niet, dan doet de dagelijkse
                // opschoontaak het later alsnog.
                Log::warning('Demo direct opruimen mislukt', ['company' => $company->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route($target);
    }
}
