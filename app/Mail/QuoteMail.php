<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quote $quote,
        public string $pdf,
    ) {}

    public function envelope(): Envelope
    {
        // Onder een handelsnaam? Dan is dát de afzendernaam die de klant ziet.
        $company = $this->quote->brandedCompany();
        $replyTo = $company?->email ?: $company?->copy_email;

        // Eigen onderwerp (Instellingen → E-mailteksten), anders de standaard.
        $customSubject = $company->emailText('quote_subject');

        // Afzendernaam = de ondernemer, antwoorden gaan rechtstreeks naar hem.
        return new Envelope(
            from: new Address(config('mail.from.address'), $company?->name ?: config('mail.from.name')),
            replyTo: $replyTo ? [new Address($replyTo, $company->name ?: null)] : [],
            subject: $customSubject
                ? \App\Support\MailText::apply($customSubject, \App\Support\MailText::quoteVars($this->quote, $company))
                : __('doc.mail_quote_subject', [
                    'number' => $this->quote->number,
                    'company' => $company->name ?? 'EasyInvoice',
                ]),
        );
    }

    public function content(): Content
    {
        $company = $this->quote->brandedCompany();

        // Eigen standaardtekst; een intro op de offerte zelf gaat vóór.
        $customBody = $company->emailText('quote_body');

        return new Content(
            view: 'emails.quote',
            with: [
                'quote' => $this->quote,
                'company' => $company,
                'customBody' => $customBody
                    ? \App\Support\MailText::apply($customBody, \App\Support\MailText::quoteVars($this->quote, $company))
                    : null,
            ],
        );
    }

    public function attachments(): array
    {
        $name = ($this->quote->number ?: 'offerte-'.$this->quote->id).'.pdf';

        $attachments = [
            Attachment::fromData(fn () => $this->pdf, $name)->withMime('application/pdf'),
        ];

        // Bijlagen die voor de klant zijn bedoeld (bijv. een specificatie of
        // plan van aanpak). Met een totaalbudget zodat de mail bezorgbaar blijft.
        $budget = 15 * 1024 * 1024;
        $customerFiles = $this->quote->attachments()
            ->withoutGlobalScope('company')
            ->where('for_customer', true)
            ->orderBy('id')
            ->get();
        foreach ($customerFiles as $file) {
            $contents = $file->contents();
            if ($contents === null || strlen($contents) > $budget) {
                continue;
            }
            $budget -= strlen($contents);
            $attachments[] = Attachment::fromData(fn () => $contents, $file->filename)
                ->withMime($file->mime_type ?: 'application/octet-stream');
        }

        return $attachments;
    }
}
