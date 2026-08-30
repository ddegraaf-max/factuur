<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;

class PasswordResetLinkController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Verstuur de link. We tonen altijd dezelfde melding, ongeacht of het
        // adres bestaat, zodat we geen bestaande accounts verraden.
        Password::sendResetLink($request->only('email'));

        return back()->with(
            'status',
            __('Als dit e-mailadres bij ons bekend is, ontvang je zo een link om je wachtwoord opnieuw in te stellen.')
        );
    }
}
