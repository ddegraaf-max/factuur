<?php

namespace App\Mail;

use App\Models\TenderRequest;
use App\Support\Sender;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail aan een onderaannemer over een prijsaanvraag: de aanvraag zelf, een
 * herinnering, de gunning of een nette afwijzing. Afzender is de ondernemer
 * (bedrijfsnaam, antwoorden gaan naar zijn adres), nooit het pakket zelf.
 */
class TenderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TenderRequest $tenderRequest,
        public string $kind = 'request', // request | reminder | award | reject
    ) {}

    public function envelope(): Envelope
    {
        $round = $this->tenderRequest->round;
        $company = $round->company;
        $vars = ['package' => $round->title, 'company' => $company->name];

        $subject = match ($this->kind) {
            'reminder' => __('Herinnering: prijsaanvraag :package — :company', $vars),
            'award' => __('Opdracht: :package — :company', $vars),
            'reject' => __('Prijsaanvraag :package — niet gegund', $vars),
            default => __('Prijsaanvraag: :package — :company', $vars),
        };

        return new Envelope(
            from: Sender::address($company, $company->name ?: config('mail.from.name')),
            replyTo: array_filter([$company->email ? new Address($company->email, $company->name ?: null) : null]),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $round = $this->tenderRequest->round;

        return new Content(
            view: 'emails.tender',
            with: [
                'kind' => $this->kind,
                'request' => $this->tenderRequest,
                'round' => $round,
                'company' => $round->company,
                'subcontractor' => $this->tenderRequest->subcontractor,
                'url' => $this->tenderRequest->responseUrl(),
            ],
        );
    }
}
