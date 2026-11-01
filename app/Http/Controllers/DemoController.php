<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use App\Models\PageView;
use App\Services\DemoAccess;
use App\Services\DemoDataBuilder;
use App\Services\Fachora\DemoBuilder as FachoraDemoBuilder;
use App\Support\Brand;
use App\Support\Market;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * De demo op aanvraag (1.86.0). De demo stond open voor iedereen; nu laat een
 * bezoeker zijn e-mailadres achter, keurt de eigenaar goed (knop in de mail
 * of op de eigenaarpagina) en krijgt de aanvrager een persoonlijke link
 * waarmee hij de demo start. Zonder goedgekeurde link start er niets.
 */
class DemoController extends Controller
{
    /** Introductiepagina met het aanvraagformulier. */
    public function show(Request $request)
    {
        // Zit je al in een demo? Dan meteen door naar het dashboard.
        if ($request->user()?->company?->is_demo) {
            return redirect()->route('dashboard');
        }

        return $this->page();
    }

    /** Toegang aanvragen: vastleggen en de eigenaar mailen. */
    public function request(Request $request, DemoAccess $access): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:600'],
        ]);

        $access->request($data, $request->ip());

        return redirect()->route('demo')->with('demo_requested', true);
    }

    /** De persoonlijke link uit de mail: dezelfde pagina, nu met de startknop. */
    public function landing(Request $request, string $token)
    {
        if ($request->user()?->company?->is_demo) {
            return redirect()->route('dashboard');
        }

        if (! $this->usable($token)) {
            return $this->expired();
        }

        return $this->page($token);
    }

    /** Bouw een verse demo-omgeving en log de bezoeker in — alleen met een goedgekeurde link. */
    public function start(Request $request, string $token, DemoDataBuilder $builder, DemoAccess $access): RedirectResponse
    {
        if ($request->user()?->company?->is_demo) {
            return redirect()->route('dashboard');
        }

        // Een ingelogde échte gebruiker mag zijn eigen sessie niet kwijtraken.
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        $demoRequest = $this->usable($token);
        if (! $demoRequest) {
            return $this->expired();
        }

        try {
            $user = Brand::is('fachora') ? app(FachoraDemoBuilder::class)->build() : $builder->build();
        } catch (\Throwable $e) {
            Log::error('Demo-omgeving aanmaken mislukt', ['error' => $e->getMessage()]);

            return back()->with('error', __('De demo kon even niet worden gestart. Probeer het zo nog eens.'));
        }

        $access->markStarted($demoRequest);

        // Mijlpaal voor de marketing-inzichten: de demo-omgeving zelf wordt later opgeruimd.
        PageView::milestone($request, PageView::EVENT_DEMO);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('is_demo', true);

        return redirect()->route('dashboard')
            ->with('flash', __('Welkom in de demo! Alle gegevens zijn voorbeelden — klik gerust overal op.'));
    }

    /** Beoordelen vanuit de mail aan de eigenaar: de aanvraag met twee knoppen. */
    public function review(string $reviewToken)
    {
        return view('demo.review', ['demoRequest' => $this->reviewable($reviewToken)]);
    }

    public function decide(string $reviewToken, string $decision, DemoAccess $access)
    {
        abort_unless(in_array($decision, ['approve', 'deny'], true), 404);
        $demoRequest = $this->reviewable($reviewToken);

        $mailed = false;
        if ($decision === 'approve') {
            $mailed = $access->approve($demoRequest);
        } else {
            $access->deny($demoRequest);
        }

        return view('demo.review', ['demoRequest' => $demoRequest->fresh(), 'done' => $decision, 'mailed' => $mailed]);
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

    private function page(?string $token = null)
    {
        $data = ['token' => $token];

        // Fachora: demo van het bedrijvenpanel (pl/en); anders de demo-uitleg per markt.
        if (Brand::is('fachora')) {
            return view(Market::view('fachora.demo'), $data);
        }

        return view(Market::view(Market::isPl() ? 'lopra-pl.demo' : 'marketing.demo'), $data);
    }

    private function usable(string $token): ?DemoRequest
    {
        $demoRequest = DemoRequest::forBrand(Brand::key())->where('token', $token)->first();

        return $demoRequest?->isUsable() ? $demoRequest : null;
    }

    private function reviewable(string $reviewToken): DemoRequest
    {
        return DemoRequest::forBrand(Brand::key())->where('review_token', $reviewToken)->firstOrFail();
    }

    private function expired(): RedirectResponse
    {
        return redirect()->route('demo')->with('error', __('Deze demolink is verlopen of nog niet goedgekeurd. Vraag hieronder opnieuw toegang aan.'));
    }
}
