<?php

namespace App\Console\Commands;

use App\Mail\DailySummaryMail;
use App\Mail\InvoiceMail;
use App\Mail\PaymentReminderMail;
use App\Mail\PaymentThanksMail;
use App\Mail\QuoteAcceptedMail;
use App\Mail\QuoteDecisionMail;
use App\Mail\QuoteMail;
use App\Mail\VerificationCodeMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\User;
use App\Services\UblGenerator;
use App\Services\VatCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Stuurt van elk berichttype één echte proefmail naar één adres.
 *
 *   php artisan mail:test jouw@adres.nl
 *
 * Alle gegevens zijn verzonnen en worden NIET opgeslagen: er wordt niets aan de
 * database toegevoegd of gewijzigd. De sjablonen, PDF's en bijlagen zijn wél
 * echt — precies wat een klant zou ontvangen.
 */
class SendTestMails extends Command
{
    protected $signature = 'mail:test {email : Ontvanger van de proefmails}
                                      {--only= : Alleen dit type (factuur|herinnering|bedankt|offerte|akkoord|beslissing|dagoverzicht|verificatie)}';

    protected $description = 'Verstuur van elk berichttype een proefmail met verzonnen gegevens.';

    public function handle(VatCalculator $vat, UblGenerator $ubl): int
    {
        $to = $this->argument('email');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error("'{$to}' is geen geldig e-mailadres.");

            return self::FAILURE;
        }

        if (config('mail.default') === 'log') {
            $this->warn('Let op: MAIL_MAILER staat op "log". De berichten belanden in het logbestand, niet in een mailbox.');
            $this->warn('Draai eerst: php artisan mail:check');
            $this->newLine();
        }

        $company = $this->fakeCompany();
        $only = $this->option('only');
        $sent = [];
        $failed = [];

        $jobs = [
            'factuur' => fn () => $this->sendInvoice($to, $company, $vat, $ubl),
            'herinnering' => fn () => $this->sendReminder($to, $company, $vat),
            'bedankt' => fn () => $this->sendThanks($to, $company, $vat),
            'offerte' => fn () => $this->sendQuote($to, $company, $vat),
            'akkoord' => fn () => $this->sendQuoteAccepted($to, $company, $vat),
            'beslissing' => fn () => $this->sendQuoteDecision($to, $company, $vat),
            'dagoverzicht' => fn () => $this->sendSummary($to, $company),
            'verificatie' => fn () => $this->sendVerification($to, $company),
        ];

        foreach ($jobs as $name => $job) {
            if ($only && $only !== $name) {
                continue;
            }

            $this->line("Versturen: {$name}…");

            try {
                $job();
                $sent[] = $name;
            } catch (\Throwable $e) {
                $failed[$name] = $e->getMessage();
                $this->error("  mislukt: ".$e->getMessage());
            }
        }

        $this->newLine();

        if ($sent) {
            $this->info('Verstuurd naar '.$to.': '.implode(', ', $sent).'.');
            $this->line('Kijk ook even in de spammap.');
        }

        if ($failed) {
            $this->newLine();
            $this->error('Mislukt:');
            foreach ($failed as $name => $message) {
                $this->line("  - {$name}: {$message}");
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /* ===================== BERICHTEN ===================== */

    private function sendInvoice(string $to, Company $company, VatCalculator $vat, UblGenerator $ubl): void
    {
        $invoice = $this->fakeInvoice($company, $vat);

        $pdf = Pdf::loadView('pdf.invoice-modern', [
            'invoice' => $invoice,
            'company' => $company,
        ])->setPaper('a4')->output();

        $xml = null;
        try {
            $xml = $ubl->generate($invoice);
        } catch (\Throwable $e) {
            $this->warn('  UBL-bijlage kon niet worden gemaakt: '.$e->getMessage());
        }

        Mail::to($to)->send(new InvoiceMail($invoice, $pdf, $xml));
    }

    private function sendReminder(string $to, Company $company, VatCalculator $vat): void
    {
        $invoice = $this->fakeInvoice($company, $vat);
        $invoice->due_date = now()->subDays(21);
        $invoice->status = 'overdue';

        $pdf = Pdf::loadView('pdf.invoice-modern', [
            'invoice' => $invoice,
            'company' => $company,
        ])->setPaper('a4')->output();

        $settings = $company->resolved_reminders;
        $eur = fn ($n) => '€ '.number_format((float) $n, 2, ',', '.');

        $vars = [
            '{klant}' => $invoice->customer_name,
            '{factuurnummer}' => $invoice->number,
            '{factuurdatum}' => $invoice->invoice_date->format('d-m-Y'),
            '{vervaldatum}' => $invoice->due_date->format('d-m-Y'),
            '{bedrag}' => $eur($invoice->total),
            '{openstaand}' => $eur($invoice->total),
            '{termijn}' => '7',
            '{iban}' => $company->iban,
            '{bedrijf}' => $company->name,
        ];

        Mail::to($to)->send(new PaymentReminderMail(
            strtr($settings['reminder_subject'], $vars),
            strtr($settings['reminder_body'], $vars),
            $invoice,
            $pdf,
        ));
    }

    private function sendThanks(string $to, Company $company, VatCalculator $vat): void
    {
        $invoice = $this->fakeInvoice($company, $vat);
        $invoice->status = 'paid';
        $invoice->paid_total = $invoice->total;
        $invoice->paid_at = now();

        $payment = new Payment([
            'kind' => 'payment',
            'amount' => $invoice->total,
            'paid_on' => now()->toDateString(),
            'method' => 'ideal',
        ]);
        $payment->exists = false;

        // De bijlage zoals de klant 'm krijgt: de factuur met stempel BETAALD.
        $pdf = Pdf::loadView('pdf.invoice-modern', [
            'invoice' => $invoice,
            'company' => $company,
            'watermarkStatus' => 'paid',
        ])->setPaper('a4')->output();

        Mail::to($to)->send(new PaymentThanksMail($invoice, $payment, $pdf));
    }

    private function sendQuote(string $to, Company $company, VatCalculator $vat): void
    {
        $quote = $this->fakeQuote($company, $vat);

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'company' => $company,
        ])->setPaper('a4')->output();

        Mail::to($to)->send(new QuoteMail($quote, $pdf));
    }

    private function sendQuoteAccepted(string $to, Company $company, VatCalculator $vat): void
    {
        $quote = $this->fakeQuote($company, $vat);
        $quote->status = 'accepted';
        $quote->accepted_at = now();
        $quote->signed_at = now();
        $quote->signed_name = 'Sanne de Vries';
        $quote->setRelation('installments', new Collection());

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'company' => $company,
        ])->setPaper('a4')->output();

        Mail::to($to)->send(new QuoteAcceptedMail($quote, $pdf));
    }

    /** De melding aan de ondernemer dat een klant heeft getekend. */
    private function sendQuoteDecision(string $to, Company $company, VatCalculator $vat): void
    {
        $quote = $this->fakeQuote($company, $vat);
        $quote->status = 'accepted';
        $quote->accepted_at = now();
        $quote->signed_at = now();
        $quote->signed_name = 'Sanne de Vries';
        $quote->signed_email = 'balie@tandartssmile.nl';

        Mail::to($to)->send(new QuoteDecisionMail($quote, true));
    }

    private function sendSummary(string $to, Company $company): void
    {
        $summary = [
            'has_news' => true,
            'overdue' => [
                'count' => 2,
                'amount' => 1738.55,
                'items' => [
                    ['number' => '2026-0041', 'customer' => 'De Vries Bouw B.V.', 'amount' => 1210.00, 'due_date' => '20-07-2026', 'days_overdue' => 21],
                    ['number' => '2026-0038', 'customer' => 'Bakkerij Het Stoepje', 'amount' => 528.55, 'due_date' => '02-07-2026', 'days_overdue' => 39],
                ],
            ],
            'due_soon' => [
                'count' => 1,
                'amount' => 907.50,
                'items' => [
                    ['number' => '2026-0044', 'customer' => 'Studio Lumen', 'amount' => 907.50, 'due_date' => '22-08-2026', 'days_overdue' => 0],
                ],
            ],
            'open' => ['count' => 3, 'amount' => 2646.05],
            'incasso' => ['count' => 1, 'amount' => 1149.50],
            'paid_yesterday' => [
                'count' => 1,
                'amount' => 1815.00,
                'items' => [
                    ['number' => '2026-0039', 'customer' => 'Janssen Advies', 'amount' => 1815.00],
                ],
            ],
            'drafts' => 2,
        ];

        Mail::to($to)->send(new DailySummaryMail($company, $summary));
    }

    private function sendVerification(string $to, Company $company): void
    {
        $user = new User([
            'name' => 'Jan Jansen',
            'email' => $to,
        ]);
        $user->setRelation('company', $company);

        Mail::to($to)->send(new VerificationCodeMail($user, '123456'));
    }

    /* ===================== VERZONNEN GEGEVENS ===================== */

    /** Een bedrijf dat alleen in het geheugen bestaat — niets wordt opgeslagen. */
    private function fakeCompany(): Company
    {
        $company = new Company([
            'name' => 'Jansen Webdesign',
            'kvk_number' => '81234567',
            'vat_number' => 'NL123456789B01',
            'iban' => 'NL12 RABO 0123 4567 89',
            'email' => 'administratie@jansenwebdesign.nl',
            'phone' => '035 - 123 45 67',
            'website' => 'www.jansenwebdesign.nl',
            'address_line' => 'Lindelaan 12',
            'postal_code' => '1211 AB',
            'city' => 'Hilversum',
            'country' => 'NL',
            'currency' => 'EUR',
            'brand_color' => '#E8231F',
            'invoice_template' => 'modern',
            'invoice_font' => 'sans',
            'default_payment_terms' => 14,
            'invoice_footer' => 'Bedankt voor de samenwerking! Dit is een proefbericht uit EasyInvoice.',
        ]);

        // Niet opslaan: dit exemplaar bestaat alleen tijdens deze opdracht.
        $company->id = 0;
        $company->exists = false;

        return $company;
    }

    private function fakeInvoice(Company $company, VatCalculator $vat): Invoice
    {
        $rows = [
            ['Website ontwerp & realisatie', 1, 1650.00, 21],
            ['Onderhoudscontract', 3, 45.00, 21],
            ['Uurtarief development', 7, 75.00, 21],
        ];

        $lineData = array_map(fn ($r) => [
            'description' => $r[0], 'quantity' => $r[1], 'unit_price' => $r[2], 'vat_rate' => $r[3],
        ], $rows);

        $totals = $vat->calculateInvoice($lineData);

        $invoice = new Invoice([
            'number' => date('Y').'-0042',
            'reference' => 'PROEFBERICHT — geen echte factuur',
            'status' => 'sent',
            'invoice_date' => now()->subDays(4),
            'due_date' => now()->addDays(10),
            'payment_terms' => 14,
            'customer_name' => 'De Vries Bouw B.V.',
            'customer_address_line' => 'Ambachtsweg 45',
            'customer_postal_code' => '3542 DG',
            'customer_city' => 'Utrecht',
            'customer_country' => 'NL',
            'customer_kvk_number' => '30123456',
            'customer_vat_number' => 'NL812345678B01',
            'customer_email' => 'administratie@devriesbouw.nl',
            'subtotal' => $totals['subtotal'],
            'vat_total' => $totals['vat_total'],
            'total' => $totals['total'],
            'paid_total' => 0,
            'vat_breakdown' => $totals['vat_breakdown'],
            'notes' => 'Dit is een testbericht van EasyInvoice. Er is geen factuur aangemaakt en er is niets opgeslagen.',
            'footer' => $company->invoice_footer,
        ]);
        $invoice->id = 0;
        $invoice->exists = false;

        $lines = new Collection();
        foreach ($rows as $index => [$description, $qty, $price, $rate]) {
            $calc = $vat->calculateLine($qty, $price, $rate);
            $line = new InvoiceLine([
                'sort_order' => $index,
                'description' => $description,
                'quantity' => $qty,
                'unit' => 'stuk',
                'unit_price' => $price,
                'vat_rate' => $rate,
                'line_subtotal' => $calc['subtotal'],
                'line_vat' => $calc['vat'],
                'line_total' => $calc['total'],
            ]);
            $line->exists = false;
            $lines->push($line);
        }

        // Relaties vooraf zetten, zodat er nooit een database-query nodig is.
        $invoice->setRelation('lines', $lines);
        $invoice->setRelation('company', $company);

        return $invoice;
    }

    private function fakeQuote(Company $company, VatCalculator $vat): Quote
    {
        $rows = [
            ['Website ontwerp & realisatie', 1, 1650.00, 21],
            ['Logo & huisstijl', 1, 480.00, 21],
        ];

        $lineData = array_map(fn ($r) => [
            'description' => $r[0], 'quantity' => $r[1], 'unit_price' => $r[2], 'vat_rate' => $r[3],
        ], $rows);

        $totals = $vat->calculateInvoice($lineData);

        $quote = new Quote([
            'number' => 'OFF-'.date('Y').'-0007',
            'status' => 'sent',
            'reference' => 'PROEFBERICHT — geen echte offerte',
            'quote_date' => now(),
            'valid_until' => now()->addDays(30),
            'customer_name' => 'Tandartspraktijk Smile',
            'customer_address_line' => 'Blaak 16',
            'customer_postal_code' => '3011 TA',
            'customer_city' => 'Rotterdam',
            'customer_country' => 'NL',
            'customer_email' => 'balie@tandartssmile.nl',
            'subtotal' => $totals['subtotal'],
            'vat_total' => $totals['vat_total'],
            'total' => $totals['total'],
            'vat_breakdown' => $totals['vat_breakdown'],
            'intro' => 'Dit is een testbericht van EasyInvoice — er is geen offerte aangemaakt en niets opgeslagen.',
            'footer' => $company->invoice_footer,
        ]);
        $quote->id = 0;
        $quote->exists = false;

        $lines = new Collection();
        foreach ($rows as $index => [$description, $qty, $price, $rate]) {
            $calc = $vat->calculateLine($qty, $price, $rate);
            $line = new QuoteLine([
                'sort_order' => $index,
                'description' => $description,
                'quantity' => $qty,
                'unit' => 'stuk',
                'unit_price' => $price,
                'vat_rate' => $rate,
                'line_subtotal' => $calc['subtotal'],
                'line_vat' => $calc['vat'],
                'line_total' => $calc['total'],
            ]);
            $line->exists = false;
            $lines->push($line);
        }

        $quote->setRelation('lines', $lines);
        $quote->setRelation('company', $company);

        return $quote;
    }
}
