<?php

namespace App\Mail;

use App\Models\DemoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** De demo is goedgekeurd: persoonlijke link naar de aanvrager, in zijn eigen taal. */
class DemoAccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DemoRequest $demoRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), (string) brand('name')),
            replyTo: [new Address((string) brand('email'), (string) brand('name'))],
            subject: __('Je demo van :brand staat klaar', ['brand' => brand('name')]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.demo-access', with: [
            'url' => route('demo.landing', $this->demoRequest->token),
            'expires' => $this->demoRequest->expires_at?->translatedFormat('j F Y'),
        ]);
    }
}
