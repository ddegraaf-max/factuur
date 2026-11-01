<?php

namespace App\Services;

use App\Mail\DemoAccessMail;
use App\Mail\DemoRequestMail;
use App\Models\DemoRequest;
use App\Support\Brand;
use App\Support\OwnerAccess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Demo op aanvraag (1.86.0): aanvraag vastleggen en de eigenaar mailen,
 * goedkeuren (persoonlijke link naar de aanvrager) of afwijzen, en direct
 * uitnodigen vanaf de eigenaarpagina. Niets gaat vanzelf: zonder
 * goedkeuring van de eigenaar start er geen demo.
 */
class DemoAccess
{
    /** @param array{email: string, name?: ?string, company?: ?string, message?: ?string} $data */
    public function request(array $data, ?string $ip = null): DemoRequest
    {
        $request = DemoRequest::create([
            'brand' => Brand::key(),
            'email' => mb_strtolower(trim($data['email'])),
            'name' => $data['name'] ?? null,
            'company' => $data['company'] ?? null,
            'message' => $data['message'] ?? null,
            'locale' => app()->getLocale(),
            'token' => DemoRequest::newToken(),
            'review_token' => DemoRequest::newToken(),
            'status' => DemoRequest::PENDING,
            'ip' => $ip,
        ]);

        $this->notifyOwner($request);

        return $request;
    }

    /**
     * Goedkeuren: de link geldig maken en de aanvrager mailen. Nog eens
     * goedkeuren verlengt de link en stuurt de mail opnieuw. Geeft terug
     * of de mail weg is.
     */
    public function approve(DemoRequest $request): bool
    {
        $request->forceFill([
            'status' => DemoRequest::APPROVED,
            'approved_at' => now(),
            'denied_at' => null,
            'expires_at' => now()->addDays(DemoRequest::VALID_DAYS),
        ])->save();

        return $this->sendAccess($request);
    }

    /** Afwijzen: de link werkt niet (meer); de aanvrager krijgt geen bericht. */
    public function deny(DemoRequest $request): void
    {
        $request->forceFill(['status' => DemoRequest::DENIED, 'denied_at' => now(), 'expires_at' => null])->save();
    }

    /** De eigenaar nodigt iemand direct uit: meteen goedgekeurd, link per mail. */
    public function invite(string $email, ?string $name = null, ?string $locale = null): DemoRequest
    {
        $request = DemoRequest::create([
            'brand' => Brand::key(),
            'email' => mb_strtolower(trim($email)),
            'name' => $name,
            'locale' => $locale ?: app()->getLocale(),
            'token' => DemoRequest::newToken(),
            'review_token' => DemoRequest::newToken(),
            'status' => DemoRequest::PENDING,
        ]);
        $this->approve($request);

        return $request;
    }

    /** De link is gebruikt om een demo te starten: tellen. */
    public function markStarted(DemoRequest $request): void
    {
        $request->forceFill(['starts' => $request->starts + 1, 'last_started_at' => now()])->save();
    }

    /**
     * Waar de aanvraag heen gaat: de eigenaarsadressen (MARKETING_STATS_EMAILS
     * of de eigenaar van de vrijgestelde administratie), anders het merkadres.
     *
     * @return list<string>
     */
    public function ownerEmails(): array
    {
        $emails = OwnerAccess::emails();

        return $emails ?: array_values(array_filter([(string) brand('email')]));
    }

    private function notifyOwner(DemoRequest $request): void
    {
        $to = $this->ownerEmails();
        if (! $to) {
            Log::warning('Demo-aanvraag: geen eigenaarsadres om te mailen', ['request' => $request->id]);

            return;
        }

        try {
            Mail::to($to)->locale('nl')->send(new DemoRequestMail($request));
        } catch (\Throwable $e) {
            Log::warning('Demo-aanvraag: mail aan de eigenaar mislukt', ['request' => $request->id, 'error' => $e->getMessage()]);
        }
    }

    private function sendAccess(DemoRequest $request): bool
    {
        try {
            Mail::to($request->email)->locale($request->locale ?: app()->getLocale())->send(new DemoAccessMail($request));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Demo-toegang: mail aan de aanvrager mislukt', ['request' => $request->id, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
