<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Verzoek aan Creditline Polska om een vordering te kopen (wykup wierzytelności). */
class WykupRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public User $user,
        public array $claim,
        public ?string $note = null,
    ) {}

    public function envelope(): Envelope
    {
        $company = $this->invoice->company;

        return new Envelope(
            subject: 'Wykup wierzytelności — faktura ' . $this->invoice->number . ' — ' . ($company->name ?? ''),
            replyTo: [new Address($this->user->email, $this->user->name)],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.wykup-request', with: [
            'invoice' => $this->invoice,
            'company' => $this->invoice->company,
            'user' => $this->user,
            'claim' => $this->claim,
            'note' => $this->note,
            'appUrl' => rtrim((string) config('app.url'), '/'),
        ]);
    }
}
