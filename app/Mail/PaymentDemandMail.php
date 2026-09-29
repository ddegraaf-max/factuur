<?php

namespace App\Mail;

use App\Models\PaymentDemand;
use App\Support\Sender;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * De laatste aanmaning aan de klant: uit naam van de ondernemer, met de brief
 * en de factuur als PDF en een link naar de pagina met het bedrag van vandaag.
 */
class PaymentDemandMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array<string, mixed>  $claim  de vordering op de dag van verzenden */
    public function __construct(
        public PaymentDemand $demand,
        public array $claim,
        public string $letterPdf,
        public string $invoicePdf,
        // Losse aanmaning: de kopie van de factuur die de schuldeiser meestuurde (name, data, mime).
        public ?array $copy = null,
    ) {}

    public function envelope(): Envelope
    {
        $invoice = $this->demand->invoice;
        $company = $invoice->brandedCompany();
        $replyTo = $company->email ?: $invoice->company?->email;

        // Zonder account is alleen het e-mailadres van de schuldeiser bevestigd: dan staat het pakket erbij als afzender.
        $name = $this->demand->isStandalone()
            ? __(':company via :brand', ['company' => $company->name, 'brand' => brand('name')])
            : ($company->name ?: config('mail.from.name'));

        return new Envelope(
            from: Sender::address($invoice->company, $name),
            replyTo: $replyTo ? [new Address($replyTo, $company->name ?: null)] : [],
            subject: __('Laatste aanmaning: factuur :number — :company', ['number' => $invoice->number, 'company' => $company->name]),
        );
    }

    public function content(): Content
    {
        $invoice = $this->demand->invoice;

        return new Content(
            view: 'emails.payment-demand',
            with: [
                'demand' => $this->demand,
                'invoice' => $invoice,
                'company' => $invoice->brandedCompany(),
                'claim' => $this->claim,
                'url' => $this->demand->url(),
            ],
        );
    }

    public function attachments(): array
    {
        $number = preg_replace('/[^A-Za-z0-9\-]+/', '-', (string) $this->demand->invoice->number);

        $items = [Attachment::fromData(fn () => $this->letterPdf, 'aanmaning-' . $number . '.pdf')->withMime('application/pdf')];
        if ($this->copy) {
            $items[] = Attachment::fromData(fn () => $this->copy['data'], $this->copy['name'])->withMime($this->copy['mime']);
        } elseif ($this->invoicePdf !== '') {
            $items[] = Attachment::fromData(fn () => $this->invoicePdf, ($this->demand->invoice->number ?: 'factuur') . '.pdf')->withMime('application/pdf');
        }

        return $items;
    }
}
