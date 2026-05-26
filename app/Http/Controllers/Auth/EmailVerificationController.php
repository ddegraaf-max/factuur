<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EmailVerificationController extends Controller
{
    private const MAX_ATTEMPTS = 6;
    private const RESEND_THROTTLE_SECONDS = 60;

    public function show(Request $request)
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            Auth::login($user);
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/VerifyEmail', [
            'email' => $user->email,
            'canResendIn' => $this->resendCooldownSeconds($user),
        ]);
    }

    public function verify(Request $request)
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'code.required' => 'Voer de 6-cijferige code in.',
            'code.size' => 'De code bestaat uit precies 6 cijfers.',
            'code.regex' => 'De code mag alleen cijfers bevatten.',
        ]);

        if ($user->verification_code_attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => 'Te veel pogingen. Vraag een nieuwe code aan.',
            ]);
        }

        if (! $user->verificationCodeIsValid($data['code'])) {
            $user->increment('verification_code_attempts');

            $remaining = self::MAX_ATTEMPTS - $user->verification_code_attempts;
            $msg = $remaining > 0
                ? "De code is onjuist of verlopen. Nog {$remaining} poging(en) voordat je een nieuwe moet aanvragen."
                : 'Te veel pogingen. Vraag een nieuwe code aan.';

            throw ValidationException::withMessages(['code' => $msg]);
        }

        $user->markEmailAsVerified();

        Session::forget('verifying_user_id');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('flash', 'Welkom! Je e-mailadres is bevestigd.');
    }

    public function resend(Request $request)
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login');
        }

        $cooldown = $this->resendCooldownSeconds($user);
        if ($cooldown > 0) {
            throw ValidationException::withMessages([
                'code' => "Wacht nog {$cooldown} seconde(n) voordat je een nieuwe code aanvraagt.",
            ]);
        }

        $code = $user->generateVerificationCode();
        Mail::to($user->email)->send(new VerificationCodeMail($user, $code));

        return back()->with('flash', 'Nieuwe code verstuurd naar ' . $user->email);
    }

    private function resolveUser(Request $request): ?User
    {
        $id = Session::get('verifying_user_id');
        if (! $id) return null;

        return User::find($id);
    }

    private function resendCooldownSeconds(User $user): int
    {
        if (! $user->verification_code_sent_at) return 0;

        $elapsed = (int) $user->verification_code_sent_at->diffInSeconds(now(), true);
        $remaining = self::RESEND_THROTTLE_SECONDS - $elapsed;

        return max(0, $remaining);
    }
}
