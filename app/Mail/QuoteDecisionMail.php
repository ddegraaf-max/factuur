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
        public ?string $partialBy = null, // 1.107: eerste van twee handtekeningen gezet, door wie
    ) {}

    public function envelope(): Envelope
    {
        if ($this->partialBy !== null) {
            return new Envelope(subject: __('Offerte :number: eerste handtekening gezet (1 van 2)', ['number' => $this->quote->number]));
        }

        return new Envelope(
            subject: $this->accepted
                ? __('Offerte :number is digitaal ondertekend 🎉', ['number' => $this->quote->number])
                : __('Offerte :number is afgewezen', ['number' => $this->quote->number]),
        );
    }

    /** Naam van wie nog moet tekenen (bij twee ondertekenaars). */
    public function pendingSignerName(): string
    {
        foreach ($this->quote->signers() as $signer) {
            if ($signer['signed_at'] === null) {
                return (string) $signer['name'];
            }
        }

        return '';
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-decision',
            with: [
                'quote' => $this->quote,
                'accepted' => $this->accepted,
                'partial' => $this->partialBy,
                'other' => $this->partialBy !== null ? $this->pendingSignerName() : null,
            ],
        );
    }
}
