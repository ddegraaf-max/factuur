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
     * Plan de "proefperiode is afgelopen"-mail in, te versturen op het moment
     * dat de proef afloopt. Geeft het Resend-id terug (of null als er niets is
     * ingepland).
     */
    public function scheduleTrialEndedNotice(Company $company, string $toEmail, string $firstName): ?string
    {
        if (! $this->configured() || ! $company->trial_ends_at) {
            return null;
        }

        $sendAt = $company->trial_ends_at->copy();

        // Resend staat plannen tot 30 dagen vooruit toe; alleen in de toekomst.
        if ($sendAt->isPast() || $sendAt->greaterThan(now()->addDays(30))) {
            return null;
        }

        $html = view('emails.trial-ended', [
            'firstName' => $firstName,
            'billingUrl' => route('billing.show'),
        ])->render();

        try {
            $response = Http::withToken($this->key())
                ->acceptJson()
                ->post(self::BASE.'/emails', [
                    'from' => $this->fromAddress(),
                    'to' => [$toEmail],
                    'subject' => 'Je EasyInvoice-proefperiode is afgelopen',
                    'html' => $html,
                    'scheduled_at' => $sendAt->toIso8601String(),
                ]);

            if ($response->failed()) {
                Log::warning('Resend proef-afgelopen-mail plannen mislukt', ['body' => $response->body()]);

                return null;
            }

            return $response->json('id');
        } catch (\Throwable $e) {
            Log::warning('Resend proef-afgelopen-mail plannen mislukt', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Annuleer beide vooruit ingeplande proef-mails (herinnering + afgelopen-mail),
     * bijvoorbeeld nadat er een abonnement is afgesloten.
     */
    public function cancelTrialEmails(Company $company): void
    {
        $this->cancelTrialReminder($company);
        $this->cancelTrialEndedNotice($company);
    }

    /**
     * Annuleer een eerder ingeplande proefherinnering (bijv. na een betaling).
     */
    public function cancelTrialReminder(Company $company): void
    {
        $this->cancelScheduled($company, 'trial_reminder_email_id');
    }

    /**
     * Annuleer de vooruit ingeplande "proefperiode is afgelopen"-mail.
     */
    public function cancelTrialEndedNotice(Company $company): void
    {
        $this->cancelScheduled($company, 'trial_ended_email_id');
    }

    /**
     * Annuleer een in Resend ingeplande mail waarvan het id in de opgegeven
     * kolom staat, en maak die kolom leeg.
     */
    private function cancelScheduled(Company $company, string $column): void
    {
        $id = $company->{$column};
        if (! $id || ! $this->configured()) {
            return;
        }

        try {
            Http::withToken($this->key())
                ->acceptJson()
                ->post(self::BASE.'/emails/'.$id.'/cancel');
        } catch (\Throwable $e) {
            Log::info('Resend proef-mail annuleren mislukt (mogelijk al verstuurd)', [
                'company' => $company->id,
                'column' => $column,
                'error' => $e->getMessage(),
            ]);
        }

        $company->forceFill([$column => null])->save();
    }
}
