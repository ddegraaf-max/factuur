<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request)
    {
        // Normaliseer het BTW-nummer: strip spaties/punten/streepjes en zet om
        // naar hoofdletters, zodat de unieke-check (en de opslag) werken en de
        // gebruiker het nummer mag invoeren zoals het op zijn papieren staat
        // (bijv. "NL 1234.56.789.B01" of "nl123456789b01").
        if (filled($request->input('vatNumber'))) {
            $request->merge([
                'vatNumber' => strtoupper(preg_replace('/[\s.\-]/', '', $request->input('vatNumber'))),
            ]);
        }

        // Identificatie per markt: Nederland KvK (verplicht) + btw-nummer,
        // Polen NIP (verplicht, met controlecijfer) + REGON (optioneel).
        $pl = \App\Support\Market::isPl();
        if ($pl && filled($request->input('vatNumber'))) {
            $request->merge(['vatNumber' => \App\Services\NipService::normalize($request->input('vatNumber'))]);
        }

        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:60'],
            'lastName' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'companyName' => ['required', 'string', 'max:255'],
            'companyType' => ['required', 'in:' . implode(',', array_keys(\App\Support\Market::companyTypes()))],
            'kvkNumber' => $pl
                ? ['nullable', 'regex:/^\d{9}(\d{5})?$/', 'unique:companies,kvk_number']
                : ['required', 'digits:8', 'unique:companies,kvk_number'],
            'vatNumber' => $pl
                ? ['required', 'digits:10', function ($attr, $value, $fail) { if (! \App\Services\NipService::valid($value)) { $fail(__('Nieprawidłowy numer NIP — sprawdź cyfry.')); } }, 'unique:companies,vat_number']
                : ['nullable', 'regex:/^NL\d{9}B\d{2}$/i', 'unique:companies,vat_number'],
            'acceptTerms' => ['accepted'],
            'newsletter' => ['boolean'],
        ], [
            'kvkNumber.unique' => __('Er bestaat al een account met dit KvK-nummer. Neem contact met ons op als dit onterecht is.'),
            'kvkNumber.regex' => __('Vul een geldig REGON-nummer in (9 of 14 cijfers).'),
            'vatNumber.regex' => __('Vul een geldig Nederlands BTW-nummer in, bijvoorbeeld NL123456789B01.'),
            'vatNumber.digits' => __('Vul een geldig NIP-nummer in (10 cijfers).'),
            'vatNumber.unique' => __('Er bestaat al een account met dit BTW-nummer. Neem contact met ons op als dit onterecht is.'),
        ]);

        $user = DB::transaction(function () use ($data, $pl) {
            $company = Company::create([
                'name' => $data['companyName'],
                'kvk_number' => $data['kvkNumber'] ?? null,
                'vat_number' => ! empty($data['vatNumber']) ? strtoupper($data['vatNumber']) : null,
                'email' => $data['email'],
                // Land, valuta, huisstijlkleur, betaaltermijn en factuurvoettekst volgen markt en merk.
                'country' => \App\Support\Market::country(),
                'currency' => \App\Support\Market::currency(),
                'brand_color' => (string) brand('color', '#E8231F'),
                'default_payment_terms' => $pl ? 14 : 30,
                'invoice_number_format' => $pl ? 'FV/{year}/{sequence:4}' : '{year}-{sequence:4}',
                'invoice_footer' => $pl
                    ? (string) \App\Support\Market::get('invoice_footer')
                    : 'Bedankt voor uw vertrouwen! Gelieve het factuurbedrag binnen de betaaltermijn te voldoen onder vermelding van het factuurnummer. Heeft u vragen over deze factuur? Neem gerust contact met ons op.',
                'invoice_template' => 'modern',
                'invoice_font' => 'sans',
                'price_mode' => 'excl',
                'fiscal_year_start' => 1,
                'default_send_method' => 'email',
                'results_per_page' => 25,
                'daily_notification_enabled' => true,
                'daily_notification_email' => $data['email'],
                'trial_ends_at' => now()->addDays(14),
            ]);

            $user = User::create([
                'name' => $data['firstName'] . ' ' . $data['lastName'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'company_id' => $company->id,
            ]);

            // Lidmaatschap vastleggen (meerdere administraties per gebruiker).
            $user->companies()->attach($company->id, ['role' => $user->role ?: 'owner']);

            return $user;
        });

        $code = $user->generateVerificationCode();
        Mail::to($user->email)->send(new VerificationCodeMail($user, $code));

        // Plan de proef-mails vooruit in via Resend, zodat er geen cron nodig is:
        // 1) de "proefperiode eindigt bijna"-herinnering (enkele dagen vooraf), en
        // 2) de "proefperiode is afgelopen"-mail (op het moment dat de proef stopt).
        // Beide worden geannuleerd zodra er wordt betaald.
        $resend = app(\App\Services\ResendScheduler::class);
        $reminderId = $resend->scheduleTrialReminder($user->company, $user->email, $data['firstName']);
        $endedId = $resend->scheduleTrialEndedNotice($user->company, $user->email, $data['firstName']);

        $company = $user->company;
        if ($reminderId) {
            $company->trial_reminder_email_id = $reminderId;
        }
        if ($endedId) {
            $company->trial_ended_email_id = $endedId;
        }
        if ($reminderId || $endedId) {
            $company->save();
        }

        Session::put('verifying_user_id', $user->id);

        return redirect()->route('verification.show');
    }
}
