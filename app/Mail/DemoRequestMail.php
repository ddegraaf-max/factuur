<?php

namespace App\Mail;

use App\Models\DemoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Nieuwe demo-aanvraag, naar de eigenaar: wie, waarom, en de knop om te beoordelen (Nederlands). */
class DemoRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DemoRequest $demoRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), (string) brand('name')),
            replyTo: [new Address($this->demoRequest->email, $this->demoRequest->displayName())],
            subject: 'Demo-aanvraag '.brand('name').': '.$this->demoRequest->displayName(),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.demo-request', with: [
            'reviewUrl' => route('demo.review', $this->demoRequest->review_token),
            'ownerUrl' => route('owner.demo.index'),
        ]);
    }
}
