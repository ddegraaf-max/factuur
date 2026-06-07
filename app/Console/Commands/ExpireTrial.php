<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ExpireTrial extends Command
{
    protected $signature = 'trial:expire {email} {--days=1 : Hoeveel dagen geleden de proef is verlopen}';

    protected $description = 'Zet de proefperiode van een account in het verleden, om de afloop/blokkade te testen.';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user || ! $user->company) {
            $this->error('Geen account/bedrijf gevonden voor dit e-mailadres.');

            return self::FAILURE;
        }

        $company = $user->company;
        $company->forceFill([
            'trial_ends_at' => now()->subDays((int) $this->option('days')),
            'trial_reminder_sent_at' => null,
            'trial_reminder_email_id' => null,
            'subscription_status' => null,
            'subscription_ends_at' => null,
        ])->save();

        $this->info("Proefperiode van '{$company->name}' ({$user->email}) is nu verlopen.");
        $this->line('Status: '.$company->accessStatus().' · dagen over: '.$company->daysLeft());
        $this->line('Log in als deze gebruiker — je wordt nu naar /abonnement geleid.');

        return self::SUCCESS;
    }
}
