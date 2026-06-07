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
        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:60'],
            'lastName' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'companyName' => ['required', 'string', 'max:255'],
            'companyType' => ['required', 'in:eenmanszaak,bv,vof,maatschap,stichting,vereniging,other'],
            'kvkNumber' => ['required', 'digits:8'],
            'vatNumber' => ['nullable', 'regex:/^NL\d{9}B\d{2}$/i'],
            'acceptTerms' => ['accepted'],
            'newsletter' => ['boolean'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $company = Company::create([
                'name' => $data['companyName'],
                'kvk_number' => $data['kvkNumber'],
                'vat_number' => ! empty($data['vatNumber']) ? strtoupper($data['vatNumber']) : null,
                'email' => $data['email'],
                'country' => 'NL',
                'currency' => 'EUR',
                'brand_color' => '#E8231F',
                'default_payment_terms' => 30,
                'invoice_number_format' => '{year}-{sequence:4}',
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

            return User::create([
                'name' => $data['firstName'] . ' ' . $data['lastName'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'company_id' => $company->id,
            ]);
        });

        $code = $user->generateVerificationCode();
        Mail::to($user->email)->send(new VerificationCodeMail($user, $code));

        // Plan de "proefperiode eindigt bijna"-herinnering vooruit in via Resend,
        // zodat er geen cron nodig is. Wordt geannuleerd zodra er wordt betaald.
        $reminderId = app(\App\Services\ResendScheduler::class)
            ->scheduleTrialReminder($user->company, $user->email, $data['firstName']);
        if ($reminderId) {
            $user->company->forceFill(['trial_reminder_email_id' => $reminderId])->save();
        }

        Session::put('verifying_user_id', $user->id);

        return redirect()->route('verification.show');
    }
}
