<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $pdf,
        public ?string $ubl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Factuur ' . $this->invoice->number . ' — ' . ($this->invoice->company->name ?? 'EasyInvoice'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: [
                'invoice' => $this->invoice,
                'company' => $this->invoice->company,
            ],
        );
    }

    public function attachments(): array
    {
        $base = $this->invoice->number ?: 'factuur-' . $this->invoice->id;

        $attachments = [
            Attachment::fromData(fn () => $this->pdf, $base . '.pdf')->withMime('application/pdf'),
        ];

        // E-facturatie: UBL 2.1 (NLCIUS) meesturen zodat boekhoudpakketten
        // de factuur automatisch kunnen inlezen.
        if ($this->ubl) {
            $attachments[] = Attachment::fromData(fn () => $this->ubl, $base . '-ubl.xml')->withMime('application/xml');
        }

        return $attachments;
    }
}
