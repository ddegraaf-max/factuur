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
        // Onder een handelsnaam? Dan is dát de afzendernaam die de klant ziet.
        $company = $this->invoice->brandedCompany();

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
                'company' => $this->invoice->brandedCompany(),
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

        // Bijlagen die de gebruiker voor de klant heeft gemarkeerd (bijv. een
        // urenoverzicht of specificatie). Met een totaalbudget zodat de mail
        // bezorgbaar blijft; wat niet past, blijft bereikbaar via het portaal.
        $budget = 15 * 1024 * 1024;
        $customerFiles = $this->invoice->attachments()
            ->withoutGlobalScope('company')
            ->where('for_customer', true)
            ->orderBy('id')
            ->get();

        foreach ($customerFiles as $file) {
            $contents = $file->contents();
            if ($contents === null) {
                continue;
            }
            if (strlen($contents) > $budget) {
                \Illuminate\Support\Facades\Log::warning('Factuurbijlage te groot voor de mail; alleen via portaal beschikbaar', [
                    'invoice' => $this->invoice->id,
                    'attachment' => $file->id,
                ]);
                continue;
            }
            $budget -= strlen($contents);
            $attachments[] = Attachment::fromData(fn () => $contents, $file->filename)
                ->withMime($file->mime_type ?: 'application/octet-stream');
        }

        return $attachments;
    }
}
