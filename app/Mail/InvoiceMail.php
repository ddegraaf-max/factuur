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
        $company = $this->invoice->company;

        // De ontvanger doet zaken met de ondernemer, niet met EasyInvoice: zet
        // diens bedrijfsnaam als afzender. Het e-mailadres blijft van
        // EasyInvoice — alleen dáárvoor zijn SPF en DKIM ingeregeld, en mailen
        // vanaf een vreemd domein belandt in de spamfilter.
        // Antwoorden gaan wél rechtstreeks naar de ondernemer.
        return new Envelope(
            from: new Address(config('mail.from.address'), $company->name ?: config('mail.from.name')),
            replyTo: array_filter([$this->companyReplyTo($company)]),
            subject: 'Factuur ' . $this->invoice->number . ' — ' . ($company->name ?? 'EasyInvoice'),
        );
    }

    /** Het adres waarop de ondernemer bereikbaar is voor zijn klant. */
    protected function companyReplyTo($company): ?Address
    {
        $email = $company?->email ?: $company?->copy_email;

        return $email ? new Address($email, $company->name ?: null) : null;
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
