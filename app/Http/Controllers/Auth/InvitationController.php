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
            'roleLabel' => $invitation ? __(User::ROLE_LABELS[$invitation->role] ?? $invitation->role) : null,
            'invitedBy' => $invitation?->invitedBy?->name,
            // Bestaat er al een account op dit adres? Dan geen naam/wachtwoord
            // vragen, maar de administratie aan die inlog koppelen.
            'existing' => (bool) ($invitation && User::where('email', $invitation->email)->exists()),
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->findByToken($token);

        if (! $invitation || ! $invitation->isUsable()) {
            throw ValidationException::withMessages([
                'name' => __('Deze uitnodiging is verlopen of al gebruikt. Vraag een nieuwe aan bij de beheerder.'),
            ]);
        }

        // Bestaand account? Dan wordt deze administratie aan die inlog
        // gekoppeld — de geheime link op het eigen e-mailadres is daarvoor
        // het bewijs (zelfde vertrouwensmodel als een wachtwoord-reset).
        $existing = User::where('email', $invitation->email)->first();
        if ($existing) {
            if (! $existing->isMemberOf($invitation->company)) {
                $existing->companies()->attach($invitation->company_id, ['role' => $invitation->role]);
            }
            $existing->switchToCompany($invitation->company);
            $invitation->update(['accepted_at' => now()]);

            Auth::login($existing);
            $request->session()->regenerate();

            return redirect()->route('dashboard')
                ->with('flash', __(':company is aan je account gekoppeld — je kunt altijd wisselen via het menu linksonder.', ['company' => $invitation->company?->name ?? __('De administratie')]));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required' => __('Vul je naam in.'),
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $invitation->email,
            'password' => Hash::make($data['password']),
            'company_id' => $invitation->company_id,
            'role' => $invitation->role,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->companies()->attach($invitation->company_id, ['role' => $invitation->role]);

        $invitation->update(['accepted_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('flash', __('Welkom bij :company!', ['company' => $invitation->company?->name ?? __('het team')]));
    }

    private function findByToken(string $token): ?Invitation
    {
        if (strlen($token) !== 64 || ! ctype_xdigit($token)) {
            return null;
        }

        return Invitation::with(['company', 'invitedBy'])->where('token', $token)->first();
    }
}
