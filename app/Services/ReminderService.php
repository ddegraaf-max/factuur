<?php

namespace App\Services;

use App\Mail\PaymentReminderMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\ReminderLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReminderService
{
    /** Facturen die op de eerste run niet ouder dan zoveel dagen worden meegenomen. */
    private const MAX_DAYS_PAST = 60;

    /** Verwerk alle openstaande facturen; retourneer het aantal verstuurde berichten. */
    public function run(): int
    {
        $sent = 0;

        // In console-context grijpt de company-scope niet, dus we zien alle facturen.
        // Demo-omgevingen slaan we over: daaruit mag nooit echte post vertrekken.
        $invoices = Invoice::query()
            ->where('is_credit', false)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->whereHas('company', fn ($q) => $q->where('is_demo', false))
            ->with(['company', 'lines'])
            ->get();

        foreach ($invoices as $invoice) {
            try {
                if ($this->processInvoice($invoice)) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                Log::error('Betalingsherinnering versturen mislukt', [
                    'invoice' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    /**
     * Verstuur handmatig de eerstvolgende herinnering of aanmaning voor één
     * factuur — los van het dagelijkse schema. Handig als een klant belt of
     * je er zelf eentje tussendoor wilt sturen.
     *
     * @throws \DomainException  met een uitlegbare reden wanneer het niet kan
     */
    public function sendManual(Invoice $invoice): string
    {
        $company = $invoice->company;

        if ($invoice->is_credit) {
            throw new \DomainException('Voor een creditnota versturen we geen herinnering.');
        }
        if (! $invoice->customer_email) {
            throw new \DomainException('Deze klant heeft geen e-mailadres. Vul het aan bij de klantgegevens.');
        }
        if ($invoice->status === 'draft') {
            throw new \DomainException('Verstuur de factuur eerst; een concept kan nog geen herinnering krijgen.');
        }

        $remaining = (float) $invoice->total - (float) $invoice->paid_total;
        if ($remaining <= 0) {
            throw new \DomainException('Deze factuur staat niet meer open.');
        }

        // Bepaal welke stap aan de beurt is: eerst de herinneringen, dan de aanmaningen.
        $r = $company->resolved_reminders;
        $numReminders = (int) ($r['num_reminders'] ?? 2);
        $sentReminders = ReminderLog::where('invoice_id', $invoice->id)->where('kind', 'reminder')->count();
        $sentWarnings = ReminderLog::where('invoice_id', $invoice->id)->where('kind', 'warning')->count();

        if ($sentReminders < $numReminders) {
            $kind = 'reminder';
            $label = $this->label($sentReminders + 1, 'herinnering');
            $termijn = (int) ($r['payment_term_reminder'] ?? 2);
        } else {
            $kind = 'warning';
            $label = $this->label($sentWarnings + 1, 'aanmaning');
            $termijn = (int) ($r['payment_term_warning'] ?? 1);
        }

        if (! $this->sendStep($invoice, $company, $kind, $label, $termijn, $remaining)) {
            throw new \DomainException('Er staat geen tekst ingesteld voor dit bericht. Vul die aan bij Instellingen → Herinneringen.');
        }

        return $label;
    }

    private function processInvoice(Invoice $invoice): bool
    {
        $company = $invoice->company;
        if (! $company || ! $invoice->due_date) {
            return false;
        }

        $remaining = (float) $invoice->total - (float) $invoice->paid_total;
        $r = $company->resolved_reminders;

        if ($remaining == 0) {
            return false;
        }
        if ($remaining < 0 && ! (bool) ($r['negative_outstanding'] ?? false)) {
            return false;
        }
        if (! $invoice->customer_email) {
            return false;
        }

        $numReminders = (int) ($r['num_reminders'] ?? 2);
        $ptReminder   = (int) ($r['payment_term_reminder'] ?? 2);
        $ptWarning    = (int) ($r['payment_term_warning'] ?? 1);
        $reminderDelay = (int) ($r['reminder_delay'] ?? 0);
        $warningDelay  = (int) ($r['warning_delay'] ?? 0);

        $sentReminders = ReminderLog::where('invoice_id', $invoice->id)->where('kind', 'reminder')->count();
        $sentWarnings  = ReminderLog::where('invoice_id', $invoice->id)->where('kind', 'warning')->count();

        $stepH  = $ptReminder + 1;
        $stepW  = $ptWarning + 1;
        $startH = 1 + $reminderDelay;
        $startW = $startH + $numReminders * $stepH + $warningDelay;

        $today  = now()->startOfDay();
        $dueDay = $invoice->due_date->copy()->startOfDay();

        // Volgende stap bepalen: eerst de herinneringen, daarna 2 aanmaningen.
        if ($sentReminders < $numReminders) {
            $i = $sentReminders + 1;
            $scheduled = $dueDay->copy()->addDays($startH + ($i - 1) * $stepH);
            if ($today->lt($scheduled)) {
                return false;
            }
            if ($scheduled->diffInDays($today) > self::MAX_DAYS_PAST) {
                return false; // te oud voor een eerste automatische herinnering
            }

            return $this->sendStep($invoice, $company, 'reminder', $this->label($i, 'herinnering'), $ptReminder, $remaining);
        }

        if ($sentWarnings < 2) {
            $i = $sentWarnings + 1;
            $scheduled = $dueDay->copy()->addDays($startW + ($i - 1) * $stepW);
            if ($today->lt($scheduled)) {
                return false;
            }
            if ($scheduled->diffInDays($today) > self::MAX_DAYS_PAST) {
                return false;
            }

            return $this->sendStep($invoice, $company, 'warning', $this->label($i, 'aanmaning'), $ptWarning, $remaining);
        }

        return false;
    }

    private function label(int $i, string $noun): string
    {
        $ord = [1 => 'Eerste', 2 => 'Tweede', 3 => 'Derde', 4 => 'Vierde', 5 => 'Vijfde'][$i] ?? "{$i}e";

        return "{$ord} {$noun}";
    }

    private function sendStep(Invoice $invoice, Company $company, string $kind, string $label, int $termijn, float $remaining): bool
    {
        $r = $company->resolved_reminders;
        $subjectTpl = $kind === 'warning' ? ($r['warning_subject'] ?? '') : ($r['reminder_subject'] ?? '');
        $bodyTpl    = $kind === 'warning' ? ($r['warning_body'] ?? '') : ($r['reminder_body'] ?? '');

        if (trim($bodyTpl) === '') {
            return false; // geen tekst ingesteld -> niet versturen
        }

        // De klant kent de factuur onder de gekozen handelsnaam — dus ook de
        // herinnering (tekstvariabelen én PDF-bijlage) gebruikt die huisstijl.
        $branded = $invoice->brandedCompany();

        $vars = $this->vars($invoice, $branded, $termijn, $remaining);
        $subject = strtr($subjectTpl, $vars);
        $body = strtr($bodyTpl, $vars);

        $template = in_array($branded->invoice_template, ['modern', 'classic', 'minimal'], true)
            ? $branded->invoice_template
            : 'modern';

        // De PDF-bijlage in de taal van de factuur; de herinneringstekst zelf
        // komt uit de eigen sjablonen van de ondernemer (Instellingen).
        $pdf = \App\Support\DocumentLocale::using($invoice->language, fn () => Pdf::loadView("pdf.invoice-{$template}", [
            'invoice' => $invoice,
            'company' => $branded,
        ])->setPaper('a4')->output());

        // Ook vanuit een herinnering moet de klant naar het portaal kunnen.
        $invoice->ensurePortalToken();

        Mail::to($invoice->customer_email)
            ->send(new PaymentReminderMail($subject, $body, $invoice, $pdf));

        ReminderLog::create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'type' => $label,
            'kind' => $kind,
            'channel' => 'email',
            'sent_to' => $invoice->customer_email,
            'amount_open' => $remaining,
            'sent_at' => now(),
        ]);

        return true;
    }

    private function vars(Invoice $invoice, Company $company, int $termijn, float $remaining): array
    {
        $eur = fn ($n) => '€ ' . number_format((float) $n, 2, ',', '.');

        return [
            '{klant}' => $invoice->customer_name ?? '',
            '{factuurnummer}' => $invoice->number ?? '',
            '{factuurdatum}' => optional($invoice->invoice_date)->format('d-m-Y') ?? '',
            '{vervaldatum}' => optional($invoice->due_date)->format('d-m-Y') ?? '',
            '{bedrag}' => $eur($invoice->total),
            '{openstaand}' => $eur($remaining),
            '{termijn}' => (string) $termijn,
            '{iban}' => $company->iban ?? '',
            '{bedrijf}' => $company->name ?? '',
        ];
    }
}
