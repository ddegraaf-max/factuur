<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\Market;
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
                'role_label' => __(\App\Models\User::ROLE_LABELS[$c->pivot->role] ?? $c->pivot->role),
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

        // Identificatie per markt (zie ook RegisteredUserController): Nederland
        // KvK (verplicht) + btw-nummer, Polen NIP (verplicht) + REGON (optioneel).
        $pl = Market::isPl();
        if ($pl && filled($request->input('vat_number'))) {
            $request->merge(['vat_number' => \App\Services\NipService::normalize($request->input('vat_number'))]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'kvk_number' => $pl
                ? ['nullable', 'regex:/^\d{9}(\d{5})?$/', 'unique:companies,kvk_number']
                : ['required', 'digits:8', 'unique:companies,kvk_number'],
            'vat_number' => $pl
                ? ['required', 'digits:10', function ($attr, $value, $fail) { if (! \App\Services\NipService::valid($value)) { $fail(__('Nieprawidłowy numer NIP — sprawdź cyfry.')); } }, 'unique:companies,vat_number']
                : ['nullable', 'regex:/^NL\d{9}B\d{2}$/i', 'unique:companies,vat_number'],
        ], [
            'name.required' => __('Vul de bedrijfsnaam in.'),
            'kvk_number.required' => __('Vul het KvK-nummer in.'),
            'kvk_number.digits' => __('Een KvK-nummer bestaat uit 8 cijfers.'),
            'kvk_number.regex' => __('Vul een geldig REGON-nummer in (9 of 14 cijfers).'),
            'kvk_number.unique' => __('Er bestaat al een administratie met dit KvK-nummer.'),
            'vat_number.regex' => __('Vul een geldig Nederlands BTW-nummer in, bijvoorbeeld NL123456789B01.'),
            'vat_number.digits' => __('Vul een geldig NIP-nummer in (10 cijfers).'),
            'vat_number.required' => __('Vul het NIP-nummer in.'),
            'vat_number.unique' => __('Er bestaat al een administratie met dit BTW-nummer.'),
        ]);

        $company = DB::transaction(function () use ($data, $user, $pl) {
            $company = Company::create([
                'name' => $data['name'],
                'kvk_number' => $data['kvk_number'] ?? null,
                'vat_number' => ! empty($data['vat_number']) ? strtoupper($data['vat_number']) : null,
                'email' => $user->email,
                'country' => Market::country(),
                'currency' => Market::currency(),
                'brand_color' => (string) brand('color', '#E8231F'),
                'default_payment_terms' => $pl ? 14 : 30,
                'invoice_number_format' => $pl ? 'FV/{year}/{sequence:4}' : '{year}-{sequence:4}',
                'invoice_footer' => $pl
                    ? (string) Market::get('invoice_footer')
                    : 'Bedankt voor uw vertrouwen! Gelieve het factuurbedrag binnen de betaaltermijn te voldoen onder vermelding van het factuurnummer. Heeft u vragen over deze factuur? Neem gerust contact met ons op.',
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
            ->with('flash', __('Administratie ":name" aangemaakt — vul de bedrijfsgegevens aan.', ['name' => $company->name]));
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
            ->with('flash', __('Gewisseld naar :name.', ['name' => $company->name]));
    }
}
