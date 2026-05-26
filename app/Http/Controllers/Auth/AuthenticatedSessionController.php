<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => false,
            'status' => session('status'),
        ]);
    }

    public function store(Request $request)
    {
        $creds = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $remember = (bool) $request->input('remember', false);

        $user = User::where('email', $creds['email'])->first();
        if (! $user || ! \Hash::check($creds['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Deze inloggegevens kloppen niet.',
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            // Issue a fresh code if the previous one is missing or expired
            if (! $user->verification_code || ! $user->verification_code_expires_at || now()->greaterThan($user->verification_code_expires_at)) {
                $code = $user->generateVerificationCode();
                Mail::to($user->email)->send(new VerificationCodeMail($user, $code));
            }

            Session::put('verifying_user_id', $user->id);
            return redirect()->route('verification.show');
        }

        if ($user->hasTwoFactorEnabled()) {
            // Don't log in yet; require 2FA
            Session::put('login.id', $user->id);
            Session::put('login.remember', $remember);
            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
