<?php

namespace App\Services;

use App\Mail\PaymentThanksMail;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\DocumentLocale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Bedankmail na betaling.
 *
 * Zodra een factuur volledig is voldaan door een échte betaling, krijgt de
 * klant een vriendelijk bedankje — met de factuur (stempel BETAALD) als
 * PDF-bijlage, een knop naar het klantenportaal en optioneel een reviewlink.
 *
 * Eén centrale plek voor alle drie de boekroutes:
 *  - handmatig registreren  → vinkje in het formulier (send / blocker)
 *  - bankkoppeling          → sendIfEnabled
 *  - online (iDEAL/BLIK) via Mollie → sendIfEnabled
 */
class PaymentThanksService
{
    /**
     * Waarom er (nu) geen bedankmail kan — of null als het gewoon kan.
     * De tekst is bedoeld om aan de ondernemer te tonen.
     */
    public function blocker(Invoice $invoice, bool $force = false): ?string
    {
        if ($invoice->is_credit) {
            return __('Voor een creditnota versturen we geen bedankmail.');
        }
        if ($invoice->status !== 'paid') {
            return __('De factuur is nog niet volledig betaald.');
        }
        if (! $invoice->customer_email) {
            return __('Deze klant heeft geen e-mailadres. Vul het aan bij de klantgegevens.');
        }
        if (! $this->lastPayment($invoice)) {
            return __('Er is geen echte betaling geboekt — alleen een afboeking of verrekening.');
        }
        if ($invoice->thanks_sent_at && ! $force) {
            return __('Er is al een bedankmail verstuurd op :date.', ['date' => $invoice->thanks_sent_at->translatedFormat('j F Y')]);
        }

        return null;
    }

    /**
     * Automatische route (bankkoppeling, iDEAL): alleen als de ondernemer de
     * bedankmail aan heeft staan. Fouten komen nooit naar buiten — het boeken
     * van de betaling mag hier nooit op stuklopen.
     */
    public function sendIfEnabled(Invoice $invoice): bool
    {
        $company = $invoice->company;
        if (! $company || ! $company->thanks_mail_enabled) {
            return false;
        }
        if ($this->blocker($invoice) !== null) {
            return false;
        }

        try {
            return $this->send($invoice);
        } catch (\Throwable $e) {
            Log::error('Bedankmail versturen mislukt', [
                'invoice' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verstuur de bedankmail. Met $force ook als er al eerder een is gestuurd
     * (bewuste keuze van de ondernemer op de factuurpagina).
     *
     * @throws \DomainException  met een uitlegbare reden wanneer het niet kan
     */
    public function send(Invoice $invoice, bool $force = false): bool
    {
        if ($reason = $this->blocker($invoice, $force)) {
            throw new \DomainException($reason);
        }

        $company = $invoice->company;
        // De klant kent de factuur onder de gekozen handelsnaam — de bedankmail
        // (afzender, logo, kleur én PDF) gebruikt diezelfde huisstijl.
        $branded = $invoice->brandedCompany();
        $payment = $this->lastPayment($invoice);

        $invoice->loadMissing('lines');
        $invoice->ensurePortalToken();

        // Alles in de taal van de factuur: standaardteksten én datumnotatie.
        DocumentLocale::using($invoice->language, function () use ($invoice, $company, $branded, $payment) {
            $template = $branded->resolvedInvoiceTemplate();

            // De factuur als betaalbewijs: zelfde PDF, nu met stempel BETAALD.
            $pdf = Pdf::loadView("pdf.invoice-{$template}", [
                'invoice' => $invoice,
                'company' => $branded,
                'watermarkStatus' => 'paid',
            ])->setPaper('a4')->output();

            // Uit een demo-omgeving vertrekt nooit echte post — ook niet via
            // een webhook of de console, waar de DemoMode-middleware niet grijpt.
            Mail::mailer($company->is_demo ? 'log' : null)
                ->to($invoice->customer_email)
                ->send(new PaymentThanksMail($invoice, $payment, $pdf));
        });

        $invoice->forceFill([
            'thanks_sent_at' => now(),
            'thanks_sent_to' => $invoice->customer_email,
        ])->saveQuietly();

        return true;
    }

    /** De laatste échte betaling op de factuur (geen afboeking of verrekening). */
    public function lastPayment(Invoice $invoice): ?Payment
    {
        return $invoice->payments()
            ->where('kind', 'payment')
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->first();
    }
}
