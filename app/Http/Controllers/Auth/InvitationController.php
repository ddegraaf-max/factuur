<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Uitnodiging accepteren: de uitgenodigde collega/boekhouder opent de geheime
 * link, kiest een naam en wachtwoord, en zit direct in het juiste bedrijf met
 * de juiste rol. Het e-mailadres geldt als geverifieerd — de link is immers
 * op dat adres ontvangen.
 */
class InvitationController extends Controller
{
    public function show(string $token): Response
    {
        $invitation = $this->findByToken($token);

        return Inertia::render('Auth/AcceptInvite', [
            'valid' => (bool) ($invitation && $invitation->isUsable()),
            'token' => $token,
            'email' => $invitation?->email,
            'company' => $invitation?->company?->name,
            'roleLabel' => $invitation ? (User::ROLE_LABELS[$invitation->role] ?? $invitation->role) : null,
            'invitedBy' => $invitation?->invitedBy?->name,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->findByToken($token);

        if (! $invitation || ! $invitation->isUsable()) {
            throw ValidationException::withMessages([
                'name' => 'Deze uitnodiging is verlopen of al gebruikt. Vraag een nieuwe aan bij de beheerder.',
            ]);
        }

        if (User::where('email', $invitation->email)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Er bestaat al een account met dit e-mailadres.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required' => 'Vul je naam in.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $invitation->email,
            'password' => Hash::make($data['password']),
            'company_id' => $invitation->company_id,
            'role' => $invitation->role,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $invitation->update(['accepted_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('flash', 'Welkom bij ' . ($invitation->company?->name ?? 'het team') . '!');
    }

    private function findByToken(string $token): ?Invitation
    {
        if (strlen($token) !== 64 || ! ctype_xdigit($token)) {
            return null;
        }

        return Invitation::with(['company', 'invitedBy'])->where('token', $token)->first();
    }
}
