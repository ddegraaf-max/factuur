<?php

namespace App\Mail;

use App\Models\Company;
use App\Support\Brand;
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
            ? __('Je proefperiode eindigt morgen')
            : __('Nog :days dagen in je :brand-proefperiode', ['days' => $this->daysLeft, 'brand' => Brand::name()]);

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
