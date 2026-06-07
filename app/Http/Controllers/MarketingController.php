<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class MarketingController extends Controller
{
    /**
     * Publieke homepage. Ingelogde gebruikers gaan direct naar het dashboard.
     */
    public function home(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Public/Home');
    }

    public function functies(): Response
    {
        return Inertia::render('Public/Functies');
    }

    public function prijzen(): Response
    {
        return Inertia::render('Public/Prijzen');
    }

    public function reviews(): Response
    {
        return Inertia::render('Public/Reviews');
    }

    public function changelog(): Response
    {
        return Inertia::render('Public/WatIsNieuw');
    }

    public function roadmap(): Response
    {
        return Inertia::render('Public/Roadmap');
    }

    public function over(): Response
    {
        return Inertia::render('Public/OverOns');
    }

    public function contact(): Response
    {
        return Inertia::render('Public/Contact');
    }

    public function pers(): Response
    {
        return Inertia::render('Public/Pers');
    }

    public function vacatures(): Response
    {
        return Inertia::render('Public/Vacatures');
    }

    public function helpcentrum(): Response
    {
        return Inertia::render('Public/Helpcentrum');
    }

    public function faq(): Response
    {
        return Inertia::render('Public/Faq');
    }

    public function support(): Response
    {
        return Inertia::render('Public/Support');
    }

    public function status(): Response
    {
        return Inertia::render('Public/Status');
    }

    /**
     * Verwerk het contactformulier. We slaan niets op maar loggen de aanvraag
     * en geven een bevestiging terug.
     */
    public function sendContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        Log::info('Nieuw contactbericht via website', [
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'] ?? null,
        ]);

        return back()->with('flash', 'Bedankt! Je bericht is verstuurd — we reageren binnen één werkdag.');
    }
}
