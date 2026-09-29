<?php

namespace App\Services;

use App\Mail\PaymentReminderMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\ReminderLog;
use App\Support\Audit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
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

        // Pauzes met een verstreken einddatum vervallen eerst; wat daarna nog
        // op pauze staat, slaan we over.
        $this->liftExpiredPauses();

        // In console-context grijpt de company-scope niet, dus we zien alle facturen.
        // Demo-omgevingen slaan we over: daaruit mag nooit echte post vertrekken.
        $invoices = Invoice::query()
            ->where('is_credit', false)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->whereNull('reminders_paused_at')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            // Na de laatste aanmaning (online aanmaning) geen gewone herinnering meer erachteraan.
            ->whereDoesntHave('demands', fn ($q) => $q->where('status', 'sent'))
            ->whereHas('company', fn ($q) => $q->where('is_demo', false))
            ->with(['company', 'lines'])
            ->get();

        foreach ($invoices as $invoice) {
            // Verlopen proefperiode of gestopt abonnement? Dan gaat er geen
            // automatische post meer uit naam van die administratie.
            if (! $invoice->company?->hasAccess()) {
                continue;
            }
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
        if ($invoice->remindersPaused()) {
            throw new \DomainException(__('Deze factuur staat op pauze. Hervat de herinneringen eerst om er een te kunnen sturen.'));
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

    /**
     * Zet de factuur op pauze: geen herinneringen, aanmaningen of incasso —
     * tot en met $until, of tot je zelf hervat. Op een lopende pauze pas je
     * hiermee de einddatum of de reden aan.
     *
     * @throws \DomainException  wanneer er niets te pauzeren valt
     */
    public function pause(Invoice $invoice, ?Carbon $until = null, ?string $reason = null): void
    {
        if ($invoice->is_credit || ! in_array($invoice->status, ['sent', 'partial', 'overdue'], true)) {
            throw new \DomainException(__('Alleen een verstuurde factuur die nog openstaat kun je op pauze zetten.'));
        }

        // Een verlopen pauze die de dagelijkse taak nog niet heeft opgeruimd, eerst afsluiten.
        if ($invoice->reminders_paused_at && ! $invoice->remindersPaused()) {
            $this->resume($invoice, expired: true);
        }

        $invoice->forceFill([
            'reminders_paused_at' => $invoice->reminders_paused_at ?? now(),
            'reminders_paused_until' => $until?->toDateString(),
            'reminders_pause_reason' => filled($reason) ? trim($reason) : null,
        ])->saveQuietly();

        $label = Audit::label($invoice);
        Audit::log('paused', $invoice, ($until
            ? __(':label op pauze gezet t/m :date: geen herinneringen, aanmaningen of incasso', ['label' => $label, 'date' => $until->translatedFormat('j M Y')])
            : __(':label op pauze gezet: geen herinneringen, aanmaningen of incasso', ['label' => $label]))
            . (filled($reason) ? ' — ' . trim($reason) : ''), [], $invoice->company_id);
    }

    /**
     * Heft de pauze op. Het herinneringsschema schuift op met de dagen dat de
     * pauze ná de vervaldatum liep: de klant krijgt de eerstvolgende stap op
     * dezelfde afstand als vóór de pauze, niet een inhaalslag van dag op dag.
     */
    public function resume(Invoice $invoice, bool $expired = false): void
    {
        if (! $invoice->reminders_paused_at) {
            return;
        }

        $from = $invoice->reminders_paused_at->copy()->startOfDay();
        $dueDay = $invoice->due_date?->copy()->startOfDay();
        if ($dueDay && $dueDay->gt($from)) {
            $from = $dueDay;
        }
        $to = $expired && $invoice->reminders_paused_until
            ? $invoice->reminders_paused_until->copy()->startOfDay()->addDay()
            : now()->startOfDay();
        $days = $to->gt($from) ? (int) round($from->diffInDays($to)) : 0;

        $invoice->forceFill([
            'reminders_paused_at' => null,
            'reminders_paused_until' => null,
            'reminders_pause_reason' => null,
            'reminder_shift_days' => min(3650, (int) $invoice->reminder_shift_days + $days),
        ])->saveQuietly();

        $label = Audit::label($invoice);
        Audit::log('resumed', $invoice, $expired
            ? __(':label: pauze afgelopen, herinneringen lopen weer', ['label' => $label])
            : __(':label: pauze opgeheven, herinneringen lopen weer', ['label' => $label]), [], $invoice->company_id);
    }

    /** Pauzes waarvan de einddatum voorbij is, vervallen vanzelf; geeft het aantal terug. */
    public function liftExpiredPauses(): int
    {
        $expired = Invoice::query()
            ->whereNotNull('reminders_paused_at')
            ->whereNotNull('reminders_paused_until')
            ->whereDate('reminders_paused_until', '<', now())
            ->get();

        foreach ($expired as $invoice) {
            $this->resume($invoice, expired: true);
        }

        return $expired->count();
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
        // Eerdere pauzes schuiven het hele schema op (zie resume()).
        $dueDay = $invoice->due_date->copy()->startOfDay()->addDays((int) $invoice->reminder_shift_days);

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

        // Rangtelwoord en zelfstandig naamwoord apart vertaalbaar (Pools: "Pierwsze przypomnienie").
        return trim(__($ord) . ' ' . __($noun));
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

        $template = $branded->resolvedInvoiceTemplate();

        // De PDF-bijlage in de taal van de factuur; de herinneringstekst zelf
        // komt uit de eigen sjablonen van de ondernemer (Instellingen).
        $pdf = \App\Support\DocumentLocale::using($invoice->language, fn () => Pdf::loadView("pdf.invoice-{$template}", [
            'invoice' => $invoice,
            'company' => $branded,
            // Stempel op de PDF-bijlage: HERINNERING of AANMANING.
            'watermarkStatus' => $kind === 'warning' ? 'dunning' : 'reminder',
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

        \App\Support\Audit::log('reminded', $invoice, ucfirst($label) . ' voor ' . \App\Support\Audit::label($invoice) . ' verstuurd naar ' . $invoice->customer_email, [], $company->id);

        return true;
    }

    private function vars(Invoice $invoice, Company $company, int $termijn, float $remaining): array
    {
        $eur = fn ($n) => money($n);

        return [
            '{klant}' => $invoice->customer_name ?? '',
            '{factuurnummer}' => $invoice->number ?? '',
            '{factuurdatum}' => optional($invoice->invoice_date)->format(market('date_format')) ?? '',
            '{vervaldatum}' => optional($invoice->due_date)->format(market('date_format')) ?? '',
            '{bedrag}' => $eur($invoice->total),
            '{openstaand}' => $eur($remaining),
            '{termijn}' => (string) $termijn,
            '{iban}' => $company->iban ?? '',
            '{bedrijf}' => $company->name ?? '',
        ];
    }
}
