<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Offerte :number is komen te vervallen" — naar de klant zodra de ondernemer
 * een verstuurde offerte laat vervallen, bijvoorbeeld omdat er een nieuwe
 * voor in de plaats komt. Zo ziet de klant dat de oude niet meer geldt en
 * lijken de twee offertes niet dubbel. Bewust zonder PDF: de vervallen
 * offerte hoeft niet nog eens de deur uit.
 */
class QuoteWithdrawnMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quote $quote,
    ) {}

    public function envelope(): Envelope
    {
        $company = $this->quote->brandedCompany();
        $replyTo = $company?->email ?: $company?->copy_email;

        return new Envelope(
            from: \App\Support\Sender::address($company, $company->name ?: config('mail.from.name')),
            replyTo: $replyTo ? [new Address($replyTo, $company->name ?: null)] : [],
            subject: __('doc.mail_withdrawn_subject', ['number' => $this->quote->number]),
        );
    }

    public function content(): Content
    {
        $company = $this->quote->brandedCompany();
        $replacement = $this->quote->replacedBy;

        return new Content(
            view: 'emails.quote-withdrawn',
            with: [
                'quote' => $this->quote,
                'company' => $company,
                'replacement' => $replacement,
                // Alleen een link als de nieuwe offerte al bij de klant ligt.
                'replacementUrl' => $replacement && in_array($replacement->status, ['sent', 'expired', 'accepted'], true)
                    ? $replacement->portalUrl()
                    : null,
            ],
        );
    }
}
