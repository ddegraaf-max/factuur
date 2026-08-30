<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Bericht aan de ondernemer zodra zijn klant de offerte in het portaal heeft
 * ondertekend of afgewezen. In de taal van de markt: dit is interne post.
 */
class QuoteDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quote $quote,
        public bool $accepted,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->accepted
                ? __('Offerte :number is digitaal ondertekend 🎉', ['number' => $this->quote->number])
                : __('Offerte :number is afgewezen', ['number' => $this->quote->number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-decision',
            with: [
                'quote' => $this->quote,
                'accepted' => $this->accepted,
            ],
        );
    }
}
