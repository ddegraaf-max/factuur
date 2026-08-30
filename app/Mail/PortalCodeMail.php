<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** De 6-cijferige toegangscode voor het klantenportaal. */
class PortalCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Je toegangscode voor het klantenportaal: :code', ['code' => $this->code]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.portal-code',
            with: ['code' => $this->code],
        );
    }
}
