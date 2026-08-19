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

        // Afzendernaam = de ondernemer, antwoorden gaan rechtstreeks naar hem.
        return new Envelope(
            from: new Address(config('mail.from.address'), $company?->name ?: config('mail.from.name')),
            replyTo: $replyTo ? [new Address($replyTo, $company->name ?: null)] : [],
            subject: 'Offerte '.$this->quote->number.' — '.($company->name ?? 'EasyInvoice'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote',
            with: [
                'quote' => $this->quote,
                'company' => $this->quote->brandedCompany(),
            ],
        );
    }

    public function attachments(): array
    {
        $name = ($this->quote->number ?: 'offerte-'.$this->quote->id).'.pdf';

        return [
            Attachment::fromData(fn () => $this->pdf, $name)->withMime('application/pdf'),
        ];
    }
}
