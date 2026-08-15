<?php

namespace App\Console\Commands;

use App\Mail\DailySummaryMail;
use App\Models\Company;
use App\Services\DailySummaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailySummaries extends Command
{
    protected $signature = 'summaries:send';

    protected $description = 'Stuur het dagelijkse overzicht naar bedrijven die dat aan hebben staan.';

    public function handle(DailySummaryService $summaries): int
    {
        $companies = Company::query()
            ->where('is_demo', false)
            ->where('daily_notification_enabled', true)
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($companies as $company) {
            // Geen toegang meer (proef verlopen, niet betaald)? Dan geen post.
            if (! $company->hasAccess()) {
                $skipped++;
                continue;
            }

            $to = $company->daily_notification_email
                ?: $company->email
                ?: $company->users()->value('email');

            if (! $to) {
                $skipped++;
                continue;
            }

            try {
                $summary = $summaries->gather($company);

                // Niets te melden → geen mail. Een leeg dagoverzicht elke ochtend
                // is precies het soort bericht dat mensen gaan negeren.
                if (! $summary['has_news']) {
                    $skipped++;
                    continue;
                }

                Mail::to($to)->send(new DailySummaryMail($company, $summary));
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Dagoverzicht versturen mislukt', [
                    'company' => $company->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Dagoverzichten verstuurd: {$sent} (overgeslagen: {$skipped}).");

        return self::SUCCESS;
    }
}
