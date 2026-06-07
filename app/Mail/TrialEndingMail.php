<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEndingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public int $daysLeft,
        public string $firstName,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->daysLeft === 1
            ? 'Je proefperiode eindigt morgen'
            : "Nog {$this->daysLeft} dagen in je EasyInvoice-proefperiode";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-ending',
            with: [
                'firstName' => $this->firstName,
                'daysLeft' => $this->daysLeft,
                'billingUrl' => route('billing.show'),
            ],
        );
    }
}
