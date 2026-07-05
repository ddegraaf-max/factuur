<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionCanceledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public string $firstName,
        public ?string $accessUntil,
        public bool $ended,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Je EasyInvoice-abonnement is opgezegd',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-canceled',
            with: [
                'firstName' => $this->firstName,
                'accessUntil' => $this->accessUntil,
                'ended' => $this->ended,
                'billingUrl' => route('billing.show'),
            ],
        );
    }
}
