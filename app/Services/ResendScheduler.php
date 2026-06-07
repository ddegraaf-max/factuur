<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Plant en annuleert e-mails via de Resend HTTP-API.
 * Wordt gebruikt om de "proefperiode eindigt bijna"-mail vooruit in te plannen
 * (tot 30 dagen), zodat er geen cron nodig is.
 */
class ResendScheduler
{
    private const BASE = 'https://api.resend.com';

    /** Dagen vóór het einde van de proef dat we de herinnering sturen. */
    public const REMIND_DAYS_BEFORE = 3;

    private function key(): ?string
    {
        // RESEND_KEY, of anders het Resend-API-wachtwoord uit de mailconfig.
        return config('services.resend.key') ?: config('mail.mailers.smtp.password');
    }

    public function configured(): bool
    {
        return ! empty($this->key());
    }

    private function fromAddress(): string
    {
        $address = config('mail.from.address', 'hallo@easyinvoice.nl');
        $name = config('mail.from.name', 'EasyInvoice');

        return $name ? "{$name} <{$address}>" : $address;
    }

    /**
     * Plan de proefherinnering in voor een bedrijf. Geeft het Resend-id terug
     * (of null als er niets is ingepland).
     */
    public function scheduleTrialReminder(Company $company, string $toEmail, string $firstName): ?string
    {
        if (! $this->configured() || ! $company->trial_ends_at) {
            return null;
        }

        $sendAt = $company->trial_ends_at->copy()->subDays(self::REMIND_DAYS_BEFORE);

        // Resend staat plannen tot 30 dagen vooruit toe; alleen in de toekomst.
        if ($sendAt->isPast() || $sendAt->greaterThan(now()->addDays(30))) {
            return null;
        }

        $html = view('emails.trial-ending', [
            'firstName' => $firstName,
            'daysLeft' => self::REMIND_DAYS_BEFORE,
            'billingUrl' => route('billing.show'),
        ])->render();

        try {
            $response = Http::withToken($this->key())
                ->acceptJson()
                ->post(self::BASE.'/emails', [
                    'from' => $this->fromAddress(),
                    'to' => [$toEmail],
                    'subject' => 'Nog '.self::REMIND_DAYS_BEFORE.' dagen in je EasyInvoice-proefperiode',
                    'html' => $html,
                    'scheduled_at' => $sendAt->toIso8601String(),
                ]);

            if ($response->failed()) {
                Log::warning('Resend proefherinnering plannen mislukt', ['body' => $response->body()]);

                return null;
            }

            return $response->json('id');
        } catch (\Throwable $e) {
            Log::warning('Resend proefherinnering plannen mislukt', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Annuleer een eerder ingeplande proefherinnering (bijv. na een betaling).
     */
    public function cancelTrialReminder(Company $company): void
    {
        $id = $company->trial_reminder_email_id;
        if (! $id || ! $this->configured()) {
            return;
        }

        try {
            Http::withToken($this->key())
                ->acceptJson()
                ->post(self::BASE.'/emails/'.$id.'/cancel');
        } catch (\Throwable $e) {
            Log::info('Resend proefherinnering annuleren mislukt (mogelijk al verstuurd)', [
                'company' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }

        $company->forceFill(['trial_reminder_email_id' => null])->save();
    }
}
