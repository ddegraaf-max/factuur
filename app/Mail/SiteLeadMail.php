<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\SiteLead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Nieuw bericht via het contactformulier van de website van een administratie, naar de ondernemer. */
class SiteLeadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Company $company, public SiteLead $lead)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nieuw bericht via je website van ' . $this->lead->name,
            replyTo: [$this->lead->email],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.site-lead');
    }
}
