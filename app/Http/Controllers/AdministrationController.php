<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Meerdere administraties onder één inlog: elke administratie is een eigen
 * bedrijf met eigen KvK, klanten, facturen, nummering en abonnement. De
 * gebruiker wisselt moeiteloos; users.company_id wijst altijd naar de
 * actieve administratie zodat alle bestaande scoping blijft werken.
 */
class AdministrationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $administrations = $user->companies()
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'kvk_number' => $c->kvk_number,
                'role' => $c->pivot->role,
                'role_label' => \App\Models\User::ROLE_LABELS[$c->pivot->role] ?? $c->pivot->role,
                'is_active' => $c->id === $user->company_id,
                'subscription' => $c->subscriptionSummary(),
            ]);

        return Inertia::render('Settings/Administraties', [
            'administrations' => $administrations,
        ]);
    }

    /** Nieuwe administratie: eigen bedrijf met eigen proefperiode; jij wordt beheerder. */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (filled($request->input('vat_number'))) {
            $request->merge(['vat_number' => strtoupper(preg_replace('/[\s.\-]/', '', $request->input('vat_number')))]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'kvk_number' => ['required', 'digits:8', 'unique:companies,kvk_number'],
            'vat_number' => ['nullable', 'regex:/^NL\d{9}B\d{2}$/i', 'unique:companies,vat_number'],
        ], [
            'name.required' => 'Vul de bedrijfsnaam in.',
            'kvk_number.required' => 'Vul het KvK-nummer in.',
            'kvk_number.digits' => 'Een KvK-nummer bestaat uit 8 cijfers.',
            'kvk_number.unique' => 'Er bestaat al een administratie met dit KvK-nummer.',
            'vat_number.regex' => 'Vul een geldig Nederlands BTW-nummer in, bijvoorbeeld NL123456789B01.',
            'vat_number.unique' => 'Er bestaat al een administratie met dit BTW-nummer.',
        ]);

        $company = DB::transaction(function () use ($data, $user) {
            $company = Company::create([
                'name' => $data['name'],
                'kvk_number' => $data['kvk_number'],
                'vat_number' => ! empty($data['vat_number']) ? strtoupper($data['vat_number']) : null,
                'email' => $user->email,
                'country' => 'NL',
                'currency' => 'EUR',
                'brand_color' => '#E8231F',
                'default_payment_terms' => 30,
                'invoice_number_format' => '{year}-{sequence:4}',
                'invoice_footer' => 'Bedankt voor uw vertrouwen! Gelieve het factuurbedrag binnen de betaaltermijn te voldoen onder vermelding van het factuurnummer. Heeft u vragen over deze factuur? Neem gerust contact met ons op.',
                'invoice_template' => 'modern',
                'invoice_font' => 'sans',
                'price_mode' => 'excl',
                'fiscal_year_start' => 1,
                'default_send_method' => 'email',
                'results_per_page' => 25,
                'daily_notification_enabled' => true,
                'daily_notification_email' => $user->email,
                // Elke administratie heeft een eigen abonnement en dus een
                // eigen proefperiode.
                'trial_ends_at' => now()->addDays(14),
            ]);

            $user->companies()->attach($company->id, ['role' => 'owner']);

            return $company;
        });

        // Direct wisselen naar de nieuwe administratie.
        $user->switchToCompany($company);

        return redirect()->route('settings.company')
            ->with('flash', "Administratie \"{$company->name}\" aangemaakt — vul de bedrijfsgegevens aan.");
    }

    /** Wisselen van administratie (alleen waar je lid van bent). */
    public function switch(Request $request, Company $company): RedirectResponse
    {
        $user = $request->user();

        if ($company->id === $user->company_id) {
            return redirect()->route('dashboard');
        }

        if (! $user->switchToCompany($company)) {
            abort(404);
        }

        return redirect()->route('dashboard')
            ->with('flash', "Gewisseld naar {$company->name}.");
    }
}
