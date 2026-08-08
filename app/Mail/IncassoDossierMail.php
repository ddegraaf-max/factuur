<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IncassoDossierMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{name:string, data:string, mime:string}>  $files
     */
    public function __construct(
        public Invoice $invoice,
        public string $pdf,
        public array $files = [],
    ) {}

    public function envelope(): Envelope
    {
        $ref = $this->invoice->incasso_reference ?: ('factuur ' . $this->invoice->number);

        return new Envelope(
            subject: 'Nieuwe incasso-opdracht ' . $ref . ' — ' . $this->invoice->customer_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.incasso-dossier',
            with: [
                'invoice' => $this->invoice,
                'company' => $this->invoice->company,
            ],
        );
    }

    public function attachments(): array
    {
        $name = ($this->invoice->number ?: 'factuur-' . $this->invoice->id) . '.pdf';

        $items = [
            Attachment::fromData(fn () => $this->pdf, $name)->withMime('application/pdf'),
        ];

        foreach ($this->files as $f) {
            $items[] = Attachment::fromData(fn () => $f['data'], $f['name'])
                ->withMime($f['mime'] ?? 'application/octet-stream');
        }

        return $items;
    }
}
