<?php

namespace App\Mail;

use App\Models\PaymentDemand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Bericht aan de ondernemer over zijn online aanmaning: de klant heeft
 * gereageerd (response), of de termijn is voorbij zonder betaling (expired).
 */
class PaymentDemandNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array<string, mixed>  $claim  de vordering van vandaag */
    public function __construct(
        public PaymentDemand $demand,
        public string $kind, // response | expired
        public array $claim,
        public string $responseLabel = '',
    ) {}

    public function envelope(): Envelope
    {
        $invoice = $this->demand->invoice;
        $vars = ['number' => $invoice->number, 'customer' => $invoice->customer_name];

        return new Envelope(
            subject: $this->kind === 'expired'
                ? __('Termijn verstreken: factuur :number van :customer', $vars)
                : __('Reactie op je aanmaning: factuur :number van :customer', $vars),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-demand-notice',
            with: [
                'kind' => $this->kind,
                'demand' => $this->demand,
                'invoice' => $this->demand->invoice,
                'company' => $this->demand->invoice->company,
                'claim' => $this->claim,
                'responseLabel' => $this->responseLabel,
                'url' => route('invoices.show', $this->demand->invoice_id),
            ],
        );
    }
}
