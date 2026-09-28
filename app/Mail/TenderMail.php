<?php

namespace App\Mail;

use App\Models\TenderRequest;
use App\Support\IsoWeek;
use App\Support\Sender;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
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

    /** Samen niet groter dan dit, anders komt de mail niet aan; de rest staat op de reactiepagina. */
    private const ATTACHMENT_BUDGET = 15 * 1024 * 1024;

    /** @var array{attached: array<int, \App\Models\Attachment>, online: array<int, \App\Models\Attachment>}|null */
    private ?array $files = null;

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
        $files = $this->files();

        return new Content(
            view: 'emails.tender',
            with: [
                'kind' => $this->kind,
                'request' => $this->tenderRequest,
                'round' => $round,
                'company' => $round->company,
                'subcontractor' => $this->tenderRequest->subcontractor,
                'url' => $this->tenderRequest->responseUrl(),
                'startWeek' => IsoWeek::label($round->start_week),
                'attached' => array_map(fn ($file) => $file->filename, $files['attached']),
                'online' => array_map(fn ($file) => $file->filename, $files['online']),
            ],
        );
    }

    /** Tekening en bestek gaan mee met de aanvraag, de herinnering en de opdracht. */
    public function attachments(): array
    {
        return array_map(
            fn ($file) => Attachment::fromData(fn () => $file->contents(), $file->filename)
                ->withMime($file->mime_type ?: 'application/octet-stream'),
            $this->files()['attached'],
        );
    }

    /**
     * Welke bijlagen van de uitvraag passen in de mail, en welke staan alleen
     * op de reactiepagina (te groot, of samen boven het budget).
     *
     * @return array{attached: array<int, \App\Models\Attachment>, online: array<int, \App\Models\Attachment>}
     */
    private function files(): array
    {
        if ($this->files !== null) {
            return $this->files;
        }

        $this->files = ['attached' => [], 'online' => []];
        if ($this->kind === 'reject') {
            return $this->files;
        }

        $budget = self::ATTACHMENT_BUDGET;
        foreach ($this->tenderRequest->round->attachments()->get() as $file) {
            $size = (int) $file->size_bytes;
            if ($file->contents() === null) {
                continue;
            }
            if ($size > $budget) {
                $this->files['online'][] = $file;
                continue;
            }
            $budget -= $size;
            $this->files['attached'][] = $file;
        }

        return $this->files;
    }
}
