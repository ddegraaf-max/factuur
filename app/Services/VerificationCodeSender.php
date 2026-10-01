<?php

namespace App\Services;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Maakt de zescijferige code aan en stuurt hem, zonder de aanroeper te breken.
 *
 * ── Waarom dit apart staat ────────────────────────────────────────────────
 *
 * De code werd op drie plekken met `Mail::to(...)->send(...)` verstuurd:
 * bij het aanmelden, bij het inloggen met een onbevestigd account, en bij
 * "code opnieuw sturen". Nergens stond dat in een try/catch, en verstuurd wordt
 * er rechtstreeks — niet via een wachtrij. Eén weigerende mailserver gooide dus
 * een uitzondering midden in de afhandeling.
 *
 * Bij het aanmelden was dat kwaadaardig: de administratie en de gebruiker zijn
 * op dat moment al vastgelegd (de transactie eromheen is gesloten), dus de
 * bezoeker kreeg een foutpagina terwijl zijn account bestond. Opnieuw aanmelden
 * gaf dan "dit e-mailadres is al in gebruik", en inloggen liep op dezelfde mail
 * weer vast. Zo zat je klem met een account dat je niet kon gebruiken en niet
 * kon overmaken. Dat gebeurde op lopra.nl op 30-09-2026: `MAIL_PASSWORD` was
 * daar leeg, dus smtp.resend.com weigerde de inlog.
 *
 * Een mislukte mail is hinderlijk, geen reden om het account weg te gooien. De
 * code staat in de database en is met "opnieuw sturen" alsnog op te halen; deze
 * klasse zorgt er alleen voor dat de bezoeker op het invoerscherm belandt in
 * plaats van op een foutpagina, en dat de echte oorzaak in het logboek staat.
 */
class VerificationCodeSender
{
    /** Maakt een nieuwe code en verstuurt hem. Geeft terug of dat gelukt is. */
    public function send(User $user): bool
    {
        $code = $user->generateVerificationCode();

        try {
            Mail::to($user->email)->send(new VerificationCodeMail($user, $code));

            return true;
        } catch (\Throwable $e) {
            Log::error('Verificatiecode kon niet worden verstuurd', [
                'user' => $user->id,
                'fout' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
