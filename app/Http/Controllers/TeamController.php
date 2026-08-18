<?php

namespace App\Http\Controllers;

use App\Mail\TeamInviteMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Teambeheer (alleen voor beheerders): collega's en de boekhouder uitnodigen,
 * rollen aanpassen en teamleden verwijderen.
 */
class TeamController extends Controller
{
    private const INVITE_DAYS = 7;

    public function index(Request $request): Response
    {
        $me = $request->user();

        $users = User::where('company_id', $me->company_id)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'is_self' => $u->id === $me->id,
                'two_factor' => $u->hasTwoFactorEnabled(),
                'joined_label' => $u->created_at?->translatedFormat('j M Y'),
            ]);

        $invitations = Invitation::where('company_id', $me->company_id)
            ->whereNull('accepted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'email' => $i->email,
                'role' => $i->role,
                'expired' => $i->isExpired(),
                'expires_label' => $i->expires_at->translatedFormat('j M Y'),
            ]);

        return Inertia::render('Settings/Team', [
            'users' => $users,
            'invitations' => $invitations,
            'roles' => User::ROLE_LABELS,
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $me = $request->user();

        $data = $request->validate([
            'email' => ['required', 'email', 'max:180'],
            'role' => ['required', 'in:owner,staff,accountant'],
        ], [
            'email.required' => 'Vul een e-mailadres in.',
            'email.email' => 'Dit is geen geldig e-mailadres.',
        ]);

        $email = mb_strtolower(trim($data['email']));

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Er bestaat al een EasyInvoice-account met dit e-mailadres. Eén e-mailadres kan maar bij één bedrijf horen.',
            ]);
        }

        $pending = Invitation::where('company_id', $me->company_id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->first();

        if ($pending && $pending->isUsable()) {
            throw ValidationException::withMessages([
                'email' => 'Er staat al een uitnodiging klaar voor dit adres. Verstuur die zo nodig opnieuw.',
            ]);
        }

        // Een verlopen uitnodiging voor hetzelfde adres vervangen we gewoon.
        $pending?->delete();

        $invitation = Invitation::create([
            'company_id' => $me->company_id,
            'email' => $email,
            'role' => $data['role'],
            'token' => bin2hex(random_bytes(32)),
            'invited_by_user_id' => $me->id,
            'expires_at' => now()->addDays(self::INVITE_DAYS),
        ]);

        Mail::to($email)->send(new TeamInviteMail($invitation, $me->name));

        return back()->with('flash', "Uitnodiging verstuurd naar {$email}.");
    }

    public function updateRole(Request $request, User $member): RedirectResponse
    {
        $me = $request->user();
        abort_unless($member->company_id === $me->company_id, 404);

        $data = $request->validate([
            'role' => ['required', 'in:owner,staff,accountant'],
        ]);

        if ($member->id === $me->id) {
            return back()->with('error', 'Je kunt je eigen rol niet aanpassen — vraag een andere beheerder.');
        }

        if ($member->isOwner() && $data['role'] !== 'owner' && $this->ownerCount($me->company_id) === 1) {
            return back()->with('error', 'Er moet minstens één beheerder overblijven.');
        }

        $member->update(['role' => $data['role']]);

        return back()->with('flash', "Rol van {$member->name} aangepast naar " . User::ROLE_LABELS[$data['role']] . '.');
    }

    public function removeUser(Request $request, User $member): RedirectResponse
    {
        $me = $request->user();
        abort_unless($member->company_id === $me->company_id, 404);

        if ($member->id === $me->id) {
            return back()->with('error', 'Je kunt jezelf niet verwijderen.');
        }

        if ($member->isOwner() && $this->ownerCount($me->company_id) === 1) {
            return back()->with('error', 'Er moet minstens één beheerder overblijven.');
        }

        $member->delete();

        return back()->with('flash', "{$member->name} is verwijderd uit het team.");
    }

    public function resendInvite(Request $request, Invitation $invitation): RedirectResponse
    {
        $me = $request->user();
        abort_unless($invitation->company_id === $me->company_id && ! $invitation->accepted_at, 404);

        // Nieuwe geheime link en een verse termijn — de oude link vervalt.
        $invitation->update([
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addDays(self::INVITE_DAYS),
        ]);

        Mail::to($invitation->email)->send(new TeamInviteMail($invitation, $me->name));

        return back()->with('flash', "Uitnodiging opnieuw verstuurd naar {$invitation->email}.");
    }

    public function revokeInvite(Request $request, Invitation $invitation): RedirectResponse
    {
        abort_unless($invitation->company_id === $request->user()->company_id, 404);

        $invitation->delete();

        return back()->with('flash', 'Uitnodiging ingetrokken — de link werkt niet meer.');
    }

    private function ownerCount(int $companyId): int
    {
        return User::where('company_id', $companyId)->where('role', 'owner')->count();
    }
}
