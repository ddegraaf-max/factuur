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

        // Leden via de lidmaatschappen: iemand kan in meerdere administraties
        // zitten, dus de rol komt uit het lidmaatschap (pivot), niet de user.
        $users = $me->company->members()
            ->orderBy('company_user.created_at')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->pivot->role,
                'is_self' => $u->id === $me->id,
                'two_factor' => $u->hasTwoFactorEnabled(),
                'joined_label' => $u->pivot->created_at?->translatedFormat('j M Y'),
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
            'roles' => array_map(fn ($label) => (string) __($label), User::ROLE_LABELS),
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $me = $request->user();

        $data = $request->validate([
            'email' => ['required', 'email', 'max:180'],
            'role' => ['required', 'in:owner,staff,accountant'],
        ], [
            'email.required' => __('Vul een e-mailadres in.'),
            'email.email' => __('Dit is geen geldig e-mailadres.'),
        ]);

        $email = mb_strtolower(trim($data['email']));

        // Een bestaand account is prima (die persoon koppelt deze administratie
        // dan aan zijn inlog) — maar geen dubbele lidmaatschappen.
        $existing = User::where('email', $email)->first();
        if ($existing && $existing->isMemberOf($me->company)) {
            throw ValidationException::withMessages([
                'email' => __('Deze persoon is al lid van deze administratie.'),
            ]);
        }

        $pending = Invitation::where('company_id', $me->company_id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->first();

        if ($pending && $pending->isUsable()) {
            throw ValidationException::withMessages([
                'email' => __('Er staat al een uitnodiging klaar voor dit adres. Verstuur die zo nodig opnieuw.'),
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

        return back()->with('flash', __('Uitnodiging verstuurd naar :email.', ['email' => $email]));
    }

    public function updateRole(Request $request, User $member): RedirectResponse
    {
        $me = $request->user();
        $membership = $me->company->members()->whereKey($member->id)->first();
        abort_unless($membership, 404);

        $data = $request->validate([
            'role' => ['required', 'in:owner,staff,accountant'],
        ]);

        if ($member->id === $me->id) {
            return back()->with('error', __('Je kunt je eigen rol niet aanpassen — vraag een andere beheerder.'));
        }

        if ($membership->pivot->role === 'owner' && $data['role'] !== 'owner' && $this->ownerCount($me->company_id) === 1) {
            return back()->with('error', __('Er moet minstens één beheerder overblijven.'));
        }

        $me->company->members()->updateExistingPivot($member->id, ['role' => $data['role']]);

        // Is dit ook zijn actieve administratie, dan schuift de actieve rol mee.
        if ($member->company_id === $me->company_id) {
            $member->update(['role' => $data['role']]);
        }

        return back()->with('flash', __('Rol van :name aangepast naar :role.', ['name' => $member->name, 'role' => __(User::ROLE_LABELS[$data['role']])]));
    }

    public function removeUser(Request $request, User $member): RedirectResponse
    {
        $me = $request->user();
        $membership = $me->company->members()->whereKey($member->id)->first();
        abort_unless($membership, 404);

        if ($member->id === $me->id) {
            return back()->with('error', __('Je kunt jezelf niet verwijderen.'));
        }

        if ($membership->pivot->role === 'owner' && $this->ownerCount($me->company_id) === 1) {
            return back()->with('error', __('Er moet minstens één beheerder overblijven.'));
        }

        $me->company->members()->detach($member->id);

        // Heeft diegene nog andere administraties, dan blijft het account
        // bestaan en wisselt het naar de eerstvolgende; anders vervalt het.
        if ($member->company_id === $me->company_id) {
            $next = $member->companies()->orderBy('name')->first();
            if ($next) {
                $member->switchToCompany($next);
            } else {
                $member->delete();
            }
        }

        return back()->with('flash', __(':name is verwijderd uit het team.', ['name' => $member->name]));
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

        return back()->with('flash', __('Uitnodiging opnieuw verstuurd naar :email.', ['email' => $invitation->email]));
    }

    public function revokeInvite(Request $request, Invitation $invitation): RedirectResponse
    {
        abort_unless($invitation->company_id === $request->user()->company_id, 404);

        $invitation->delete();

        return back()->with('flash', __('Uitnodiging ingetrokken — de link werkt niet meer.'));
    }

    private function ownerCount(int $companyId): int
    {
        return \Illuminate\Support\Facades\DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('role', 'owner')
            ->count();
    }
}
