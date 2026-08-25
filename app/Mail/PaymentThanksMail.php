<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Payment;
use App\Support\MailText;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Bedankt voor uw betaling" — naar de klant zodra een factuur volledig is
 * voldaan. Met de factuur (stempel BETAALD) als bijlage.
 */
class PaymentThanksMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public ?Payment $payment,
        public string $pdf,
        /** Voorbeeldweergave in de browser (Instellingen): geen bijlage, logo als data-URL. */
        public bool $preview = false,
    ) {}

    public function envelope(): Envelope
    {
        // Onder een handelsnaam? Dan is dát de afzendernaam die de klant ziet.
        $company = $this->invoice->brandedCompany();
        $replyTo = $company?->email ?: $company?->copy_email;

        // Eigen onderwerp (Instellingen → E-mailteksten), anders de standaard
        // in de taal van de factuur.
        $customSubject = $company->emailText('thanks_subject');

        return new Envelope(
            from: new Address(config('mail.from.address'), $company->name ?: config('mail.from.name')),
            replyTo: $replyTo ? [new Address($replyTo, $company->name ?: null)] : [],
            subject: $customSubject
                ? MailText::apply($customSubject, MailText::thanksVars($this->invoice, $company, $this->payment))
                : __('doc.mail_thanks_subject', ['number' => $this->invoice->number]),
        );
    }

    public function content(): Content
    {
        $company = $this->invoice->brandedCompany();

        // Eigen tekst vervangt aanhef en intro; het betaaloverzicht, de
        // portaalknop en de reviewknop blijven automatisch (gegevensgestuurd).
        $customBody = $company->emailText('thanks_body');

        return new Content(
            view: 'emails.payment-thanks',
            with: [
                'invoice' => $this->invoice,
                'company' => $company,
                'payment' => $this->payment,
                'customBody' => $customBody
                    ? MailText::apply($customBody, MailText::thanksVars($this->invoice, $company, $this->payment))
                    : null,
                'methodLabel' => $this->payment?->method ? MailText::paymentMethodLabel($this->payment->method) : null,
                'reviewUrl' => $company->review_url ?: null,
                'preview' => $this->preview,
            ],
        );
    }

    public function attachments(): array
    {
        if ($this->pdf === '') {
            return [];
        }

        $name = ($this->invoice->number ?: 'factuur-'.$this->invoice->id).'.pdf';

        return [
            Attachment::fromData(fn () => $this->pdf, $name)->withMime('application/pdf'),
        ];
    }
}
