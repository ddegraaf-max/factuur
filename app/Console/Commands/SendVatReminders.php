<?php

namespace App\Console\Commands;

use App\Mail\VatReminderMail;
use App\Models\Company;
use App\Models\VatFiling;
use App\Services\VatService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Herinnert ondernemers aan de btw-aangifte: twee weken vóór de deadline en
 * nog eens drie dagen ervoor — alleen zolang het tijdvak niet als
 * "aangegeven" is gemarkeerd. Eén mail per stap per tijdvak.
 */
class SendVatReminders extends Command
{
    protected $signature = 'vat:remind';

    protected $description = 'Herinner bedrijven aan een openstaande btw-aangifte (14 en 3 dagen voor de deadline).';

    public function handle(VatService $vat): int
    {
        $companies = Company::query()
            ->where('is_demo', false)
            ->where('vat_reminder_enabled', true)
            ->get();

        $sent = 0;

        foreach ($companies as $company) {
            if (! $company->hasAccess()) {
                continue;
            }

            $to = $company->daily_notification_email
                ?: $company->email
                ?: $company->users()->value('email');
            if (! $to) {
                continue;
            }

            try {
                $due = $vat->attention($company)['due'];
                if (! $due) {
                    continue;
                }

                $days = (int) $due['days_left'];
                $filing = VatFiling::withoutGlobalScope('company')->firstOrNew([
                    'company_id' => $company->id,
                    'year' => $due['year'],
                    'period_type' => $due['type'],
                    'period' => $due['period'],
                ]);

                if ($days <= 3 && ! $filing->reminded_final_at) {
                    Mail::to($to)->send(new VatReminderMail($company, $due, final: true));
                    $filing->reminded_final_at = now();
                } elseif ($days <= 14 && ! $filing->reminded_at) {
                    Mail::to($to)->send(new VatReminderMail($company, $due));
                    $filing->reminded_at = now();
                } else {
                    continue;
                }

                $filing->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Btw-herinnering versturen mislukt', [
                    'company' => $company->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Btw-herinneringen verstuurd: {$sent}.");

        return self::SUCCESS;
    }
}
