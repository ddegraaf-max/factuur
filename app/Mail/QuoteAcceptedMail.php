<?php

namespace App\Mail;

use App\Models\Quote;
use App\Support\MailText;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Bedankt voor uw akkoord" — naar de klant zodra hij een offerte heeft
 * geaccepteerd (digitaal ondertekend in het portaal, of door de ondernemer
 * gemarkeerd). Met de (ondertekende) offerte als PDF: zo hebben beide
 * partijen hetzelfde document.
 */
class QuoteAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quote $quote,
        public string $pdf,
        /** Voorbeeldweergave in de browser (Instellingen): geen bijlage, logo als data-URL. */
        public bool $preview = false,
    ) {}

    public function envelope(): Envelope
    {
        $company = $this->quote->brandedCompany();
        $replyTo = $company?->email ?: $company?->copy_email;
        $customSubject = $company->emailText('accept_subject');

        return new Envelope(
            from: \App\Support\Sender::address($company, $company->name ?: config('mail.from.name')),
            replyTo: $replyTo ? [new Address($replyTo, $company->name ?: null)] : [],
            subject: $customSubject
                ? MailText::apply($customSubject, MailText::acceptVars($this->quote, $company))
                : __('doc.mail_accept_subject', ['number' => $this->quote->number]),
        );
    }

    public function content(): Content
    {
        $company = $this->quote->brandedCompany();
        $customBody = $company->emailText('accept_body');

        return new Content(
            view: 'emails.quote-accepted',
            with: [
                'quote' => $this->quote,
                'company' => $company,
                'customBody' => $customBody
                    ? MailText::apply($customBody, MailText::acceptVars($this->quote, $company))
                    : null,
                'preview' => $this->preview,
            ],
        );
    }

    public function attachments(): array
    {
        if ($this->pdf === '') {
            return [];
        }

        $name = ($this->quote->number ?: 'offerte-'.$this->quote->id).'.pdf';

        return [
            Attachment::fromData(fn () => $this->pdf, $name)->withMime('application/pdf'),
        ];
    }
}
