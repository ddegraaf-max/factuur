<?php

namespace App\Console\Commands;

use App\Mail\TrialEndingMail;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTrialReminders extends Command
{
    protected $signature = 'trials:remind {--days=3 : Verstuur wanneer de proefperiode binnen X dagen eindigt}';

    protected $description = 'Stuur een herinnering naar bedrijven waarvan de proefperiode binnenkort eindigt.';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $companies = Company::query()
            ->whereNull('subscription_ends_at')          // nog geen betaald abonnement
            ->whereNull('trial_reminder_sent_at')        // nog geen herinnering verstuurd
            ->whereNull('trial_reminder_email_id')       // niet al via Resend ingepland
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->where('trial_ends_at', '<=', now()->addDays($days))
            ->get();

        $sent = 0;

        foreach ($companies as $company) {
            // Dubbelcheck via de modellogica (proef nog actief, geen abonnement).
            if (! $company->onTrial()) {
                continue;
            }

            $daysLeft = max(1, $company->daysLeft());
            $recipients = $company->users()->whereNotNull('email')->get();

            if ($recipients->isEmpty() && $company->email) {
                $recipients = collect([(object) ['email' => $company->email, 'name' => $company->name]]);
            }

            foreach ($recipients as $user) {
                $firstName = explode(' ', trim($user->name ?? ''))[0] ?: 'daar';

                try {
                    Mail::to($user->email)->send(new TrialEndingMail($company, $daysLeft, $firstName));
                    $sent++;
                } catch (\Throwable $e) {
                    Log::error('Versturen proefherinnering mislukt', [
                        'company' => $company->id,
                        'email' => $user->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $company->forceFill(['trial_reminder_sent_at' => now()])->save();
        }

        $this->info("Proefherinneringen verstuurd: {$sent} (bedrijven: {$companies->count()}).");

        return self::SUCCESS;
    }
}
