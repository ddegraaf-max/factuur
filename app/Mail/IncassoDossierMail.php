<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
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
        $ref = $this->invoice->incasso_reference ?: __('factuur :number', ['number' => $this->invoice->number]);
        $company = $this->invoice->company;
        $replyTo = $company?->email ?: $company?->copy_email;

        // De deurwaarder moet de schuldeiser direct kunnen bereiken.
        return new Envelope(
            from: \App\Support\Sender::address($company, $company?->name ?: config('mail.from.name')),
            replyTo: $replyTo ? [new Address($replyTo, $company->name ?: null)] : [],
            subject: __('Nieuwe incasso-opdracht :ref — :customer', ['ref' => $ref, 'customer' => $this->invoice->customer_name]),
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
