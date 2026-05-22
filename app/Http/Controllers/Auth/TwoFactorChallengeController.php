<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function show()
    {
        if (! Session::has('login.id')) {
            return redirect()->route('login');
        }
        return Inertia::render('Auth/TwoFactor', [
            'use_backup' => false,
        ]);
    }

    public function store(Request $request)
    {
        $id = Session::get('login.id');
        if (! $id) return redirect()->route('login');

        $user = User::findOrFail($id);

        if ($request->filled('code')) {
            $request->validate(['code' => 'required|digits:6']);
            $secret = decrypt($user->two_factor_secret);
            $g2fa = new Google2FA();
            if (! $g2fa->verifyKey($secret, $request->input('code'))) {
                throw ValidationException::withMessages(['code' => 'Ongeldige verificatiecode.']);
            }
        } elseif ($request->filled('recovery_code')) {
            $request->validate(['recovery_code' => 'required|string']);
            $codes = $user->recoveryCodes();
            $given = trim($request->input('recovery_code'));
            $matched = collect($codes)->first(fn ($c) =>
                str_replace('-', '', $c) === str_replace('-', '', $given)
            );
            if (! $matched) {
                throw ValidationException::withMessages(['recovery_code' => 'Ongeldige backup-code.']);
            }
            // Remove used code
            $remaining = collect($codes)->reject(fn ($c) => $c === $matched)->values()->all();
            $user->update(['two_factor_recovery_codes' => encrypt(json_encode($remaining))]);
        } else {
            throw ValidationException::withMessages(['code' => 'Vul een code in.']);
        }

        Auth::login($user, Session::pull('login.remember', false));
        Session::forget('login.id');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
