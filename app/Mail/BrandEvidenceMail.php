<?php

namespace App\Mail;

use App\Models\BrandDossier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Maandelijks merkgebruik-dossier naar de eigenaar — de mailbox is het archief. */
class BrandEvidenceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BrandDossier $dossier,
        /** @var array<string, string> bestandsnaam => inhoud */
        public array $files,
        public string $manifestJson,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Merkgebruik-dossier EasyInvoice — ' . $this->dossier->month);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.brand-evidence', with: ['dossier' => $this->dossier]);
    }

    public function attachments(): array
    {
        $mimes = ['txt' => 'text/plain', 'csv' => 'text/csv', 'html' => 'text/html', 'pdf' => 'application/pdf', 'png' => 'image/png', 'json' => 'application/json'];
        $attachments = [];
        foreach ($this->files as $name => $contents) {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $attachments[] = Attachment::fromData(fn () => $contents, $name)->withMime($mimes[$ext] ?? 'application/octet-stream');
        }
        $attachments[] = Attachment::fromData(fn () => $this->manifestJson, 'manifest.json')->withMime('application/json');

        return $attachments;
    }
}
