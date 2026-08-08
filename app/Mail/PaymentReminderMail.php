<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyText,
        public Invoice $invoice,
        public string $pdf,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-reminder',
            with: [
                'bodyText' => $this->bodyText,
                'invoice' => $this->invoice,
                'company' => $this->invoice->company,
            ],
        );
    }

    public function attachments(): array
    {
        $name = ($this->invoice->number ?: 'factuur-' . $this->invoice->id) . '.pdf';

        return [
            Attachment::fromData(fn () => $this->pdf, $name)->withMime('application/pdf'),
        ];
    }
}
