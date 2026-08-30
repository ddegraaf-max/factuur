<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Mail\PortalCodeMail;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inloggen op het klantenportaal — zonder wachtwoord, maar mét twee stappen:
 *
 *  1. Bezit van het e-mailadres (of van de beveiligde factuurlink uit de mail).
 *  2. Een 6-cijferige code die naar dat e-mailadres wordt gestuurd en binnen
 *     10 minuten moet worden ingevoerd.
 *
 * Extra beveiliging:
 *  - codes worden gehasht opgeslagen (nooit leesbaar in de sessie);
 *  - maximaal 5 pogingen per code, daarna is een nieuwe code nodig;
 *  - rate-limiting per e-mailadres én per IP op het aanvragen van codes;
 *  - geen accountsprobing: het antwoord is altijd hetzelfde, of het adres nu
 *    facturen heeft of niet;
 *  - de sessie wordt geregenereerd na een geslaagde verificatie en verloopt
 *    automatisch na 12 uur.
 */
class PortalAuthController extends Controller
{
    public const CODE_TTL_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN = 60;      // seconden
    public const SESSION_HOURS = 12;

    /** Inlogpagina: e-mailadres invullen. */
    public function show(Request $request): Response|RedirectResponse
    {
        if (self::verifiedEmail($request)) {
            return redirect()->route('portal.index');
        }

        return Inertia::render('Portal/Login');
    }

    /** Stap 1 vanaf de inlogpagina: e-mailadres opgeven, code ontvangen. */
    public function requestCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:180'],
        ], [
            'email.required' => __('Vul je e-mailadres in.'),
            'email.email' => __('Dit is geen geldig e-mailadres.'),
        ]);

        $email = mb_strtolower(trim($data['email']));

        $this->guardSendRate($request, $email);

        $request->session()->put('portal_pending_email', $email);
        $request->session()->forget(['portal_intended', 'portal_gate']);

        $this->sendCodeIfDocumentsExist($request, $email);

        // Altijd hetzelfde antwoord — zo valt niet te achterhalen welke
        // e-mailadressen facturen hebben (geen accountsprobing).
        return redirect()->route('portal.verify.show');
    }

    /** Codepagina (na inlogformulier of via een beveiligde factuurlink). */
    public function showVerify(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('portal_pending_email');
        if (! $email) {
            return redirect()->route('portal.login');
        }

        return Inertia::render('Portal/Verify', [
            'maskedEmail' => self::maskEmail($email),
            'codeSent' => $request->session()->has('portal_code_hash'),
            'canResendIn' => $this->resendCooldown($request),
            'gate' => $request->session()->get('portal_gate'), // bedrijfsnaam + factuurnummer bij linkflow
        ]);
    }

    /** Verstuur (opnieuw) een code naar het wachtende e-mailadres. */
    public function sendCode(Request $request): RedirectResponse
    {
        $email = $request->session()->get('portal_pending_email');
        if (! $email) {
            return redirect()->route('portal.login');
        }

        $cooldown = $this->resendCooldown($request);
        if ($cooldown > 0) {
            throw ValidationException::withMessages([
                'code' => __('Wacht nog :seconds seconde(n) voordat je een nieuwe code aanvraagt.', ['seconds' => $cooldown]),
            ]);
        }

        $this->guardSendRate($request, $email);
        $this->sendCodeIfDocumentsExist($request, $email);

        return back()->with('flash', __('Code verstuurd. Kijk ook even in je spam-map.'));
    }

    /** Stap 2: de 6-cijferige code controleren. */
    public function verify(Request $request): RedirectResponse
    {
        $email = $request->session()->get('portal_pending_email');
        if (! $email) {
            return redirect()->route('portal.login');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'code.required' => __('Voer de 6-cijferige code in.'),
            'code.size' => __('De code bestaat uit precies 6 cijfers.'),
            'code.regex' => __('De code mag alleen cijfers bevatten.'),
        ]);

        $session = $request->session();
        $hash = $session->get('portal_code_hash');
        $expires = $session->get('portal_code_expires_at');
        $attempts = (int) $session->get('portal_code_attempts', 0);

        if (! $hash || ! $expires || now()->timestamp > $expires) {
            throw ValidationException::withMessages([
                'code' => __('De code is verlopen. Vraag een nieuwe code aan.'),
            ]);
        }

        if ($attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => __('Te veel pogingen. Vraag een nieuwe code aan.'),
            ]);
        }

        if (! Hash::check($data['code'], $hash)) {
            $session->put('portal_code_attempts', $attempts + 1);
            $remaining = self::MAX_ATTEMPTS - $attempts - 1;

            throw ValidationException::withMessages([
                'code' => $remaining > 0
                    ? __('De code is onjuist. Nog :remaining poging(en).', ['remaining' => $remaining])
                    : __('Te veel pogingen. Vraag een nieuwe code aan.'),
            ]);
        }

        // Geslaagd: sessie vernieuwen tegen session fixation en opschonen.
        $intended = $session->pull('portal_intended');
        $session->forget([
            'portal_pending_email', 'portal_code_hash', 'portal_code_expires_at',
            'portal_code_attempts', 'portal_code_sent_at', 'portal_gate',
        ]);
        $session->regenerate();
        $session->put('portal_email', $email);
        $session->put('portal_verified_at', now()->timestamp);

        return redirect($intended ?: route('portal.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'portal_email', 'portal_verified_at', 'portal_pending_email',
            'portal_code_hash', 'portal_code_expires_at', 'portal_code_attempts',
            'portal_code_sent_at', 'portal_intended', 'portal_gate',
        ]);
        $request->session()->regenerate();

        return redirect()->route('portal.login')->with('flash', __('Je bent uitgelogd.'));
    }

    /* ===================== Helpers ===================== */

    /** Het geverifieerde e-mailadres in deze sessie, of null (ook bij verlopen). */
    public static function verifiedEmail(Request $request): ?string
    {
        $email = $request->session()->get('portal_email');
        $verifiedAt = $request->session()->get('portal_verified_at');

        if (! $email || ! $verifiedAt) {
            return null;
        }

        if (now()->timestamp - (int) $verifiedAt > self::SESSION_HOURS * 3600) {
            $request->session()->forget(['portal_email', 'portal_verified_at']);
            return null;
        }

        return $email;
    }

    /** Maskeer een e-mailadres voor weergave: d.degraaf@voorbeeld.nl → d••@v•••••.nl */
    public static function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $tldPos = strrpos($domain, '.');
        $name = $tldPos === false ? $domain : substr($domain, 0, $tldPos);
        $tld = $tldPos === false ? '' : substr($domain, $tldPos);

        $mask = fn (string $part) => $part === ''
            ? ''
            : mb_substr($part, 0, 1) . str_repeat('•', max(2, mb_strlen($part) - 1));

        return $mask($local) . '@' . $mask($name) . $tld;
    }

    /** Rate-limiting op het versturen van codes: max 3 per 10 min per adres + IP. */
    protected function guardSendRate(Request $request, string $email): void
    {
        foreach (["portal-code:{$email}", 'portal-code-ip:' . $request->ip()] as $key) {
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
                throw ValidationException::withMessages([
                    'email' => __('Te veel aanvragen. Probeer het over :minutes minuut/minuten opnieuw.', ['minutes' => $minutes]),
                    'code' => __('Te veel aanvragen. Probeer het over :minutes minuut/minuten opnieuw.', ['minutes' => $minutes]),
                ]);
            }
            RateLimiter::hit($key, 600);
        }
    }

    /**
     * Genereer en mail een code — maar alleen als er daadwerkelijk verstuurde
     * facturen óf offertes voor dit adres bestaan (een klant krijgt vaak eerst
     * een offerte en pas later een factuur). De sessie wordt in beide gevallen
     * identiek gevuld, zodat het gedrag van buitenaf niet te onderscheiden is.
     */
    protected function sendCodeIfDocumentsExist(Request $request, string $email): void
    {
        $code = (string) random_int(100000, 999999);

        $request->session()->put([
            'portal_code_hash' => Hash::make($code),
            'portal_code_expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES)->timestamp,
            'portal_code_attempts' => 0,
            'portal_code_sent_at' => now()->timestamp,
        ]);

        $hasDocuments = Invoice::withoutGlobalScope('company')
                ->whereRaw('LOWER(customer_email) = ?', [$email])
                ->where('status', '!=', 'draft')
                ->exists()
            || \App\Models\Quote::withoutGlobalScope('company')
                ->whereRaw('LOWER(customer_email) = ?', [$email])
                ->where('status', '!=', 'draft')
                ->exists();

        if ($hasDocuments) {
            Mail::to($email)->send(new PortalCodeMail($code));
        } else {
            // Bewust geen melding naar de bezoeker (geen adres-probing), wel
            // een spoor voor support: "ik krijg geen code" is anders onvindbaar.
            \Illuminate\Support\Facades\Log::info('Portaalcode niet verstuurd: geen verstuurde facturen of offertes voor dit adres', [
                'email' => self::maskEmail($email),
            ]);
        }
    }

    protected function resendCooldown(Request $request): int
    {
        $sentAt = $request->session()->get('portal_code_sent_at');
        if (! $sentAt) {
            return 0;
        }

        return max(0, self::RESEND_COOLDOWN - (now()->timestamp - (int) $sentAt));
    }
}
