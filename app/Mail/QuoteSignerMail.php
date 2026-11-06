<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 1.107: mail aan een ondertekenaar van een offerte met twee ondertekenaars.
 * - invite: de tweede ondertekenaar krijgt zijn eigen link zodra de offerte is verstuurd;
 * - turn: de ander heeft getekend, nu is deze ondertekenaar aan de beurt;
 * - reminder: herinnering aan wie nog moet tekenen (opvolging).
 */
class QuoteSignerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quote $quote,
        public string $role, // 'customer' | 'co'
        public string $mode, // 'invite' | 'turn' | 'reminder'
    ) {}

    public function envelope(): Envelope
    {
        $number = $this->quote->number;
        $subject = match ($this->mode) {
            'turn' => __('Offerte :number: :other heeft getekend, nu uw handtekening', ['number' => $number, 'other' => $this->otherName()]),
            'reminder' => __('Herinnering: offerte :number wacht op uw handtekening', ['number' => $number]),
            default => __('Offerte :number van :company: uw handtekening is nodig', ['number' => $number, 'company' => $this->quote->brandedCompany()->name]),
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quote-signer', with: [
            'quote' => $this->quote,
            'company' => $this->quote->brandedCompany(),
            'mode' => $this->mode,
            'name' => $this->role === 'co' ? $this->quote->co_signer_name : $this->quote->customer_name,
            'other' => $this->otherName(),
            'url' => $this->role === 'co' ? $this->quote->coSignerPortalUrl() : $this->quote->portalUrl(),
        ]);
    }

    /** De andere ondertekenaar: zoals die tekende, anders zoals opgegeven. */
    public function otherName(): string
    {
        return $this->role === 'co'
            ? (string) ($this->quote->signed_name ?: $this->quote->customer_name)
            : (string) ($this->quote->co_signed_name ?: $this->quote->co_signer_name);
    }
}
