<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use PragmaRX\Google2FA\Google2FA;

class SecurityController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return Inertia::render('Settings/Security', [
            'enabled' => $user->hasTwoFactorEnabled(),
            'activated_at' => $user->two_factor_confirmed_at?->toIso8601String(),
            'backup_codes' => $user->hasTwoFactorEnabled() ? $user->recoveryCodes() : [],
        ]);
    }

    public function startSetup()
    {
        $g2fa = new Google2FA();
        $secret = $g2fa->generateSecretKey();
        $user = auth()->user();
        // Store secret temporarily (unconfirmed)
        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => null,
        ]);

        $otpauth = $g2fa->getQRCodeUrl(
            'EasyInvoice',
            $user->email,
            $secret
        );

        return Inertia::render('Settings/SecuritySetup', [
            'step' => 'qr',
            'secret' => $secret,
            'otpauth_url' => $otpauth,
        ]);
    }

    public function verifySetup(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);
        $user = auth()->user();
        if (! $user->two_factor_secret) {
            throw ValidationException::withMessages(['code' => 'Geen setup actief.']);
        }
        $secret = decrypt($user->two_factor_secret);
        $g2fa = new Google2FA();
        if (! $g2fa->verifyKey($secret, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'Ongeldige code, probeer opnieuw.']);
        }

        // Generate backup codes
        $codes = collect(range(1, 8))->map(fn () =>
            str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT) . '-' .
            str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT)
        )->all();

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
            'two_factor_confirmed_at' => now(),
        ]);

        return Inertia::render('Settings/SecuritySetup', [
            'step' => 'backup',
            'backup_codes' => $codes,
        ]);
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        auth()->user()->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
        return redirect()->route('settings.security')->with('flash', 'Tweestapsverificatie uitgeschakeld.');
    }

    public function regenerateBackupCodes()
    {
        $user = auth()->user();
        if (! $user->hasTwoFactorEnabled()) abort(403);

        $codes = collect(range(1, 8))->map(fn () =>
            str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT) . '-' .
            str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT)
        )->all();

        $user->update(['two_factor_recovery_codes' => encrypt(json_encode($codes))]);

        return back()->with('flash', 'Nieuwe backup codes gegenereerd.');
    }
}
