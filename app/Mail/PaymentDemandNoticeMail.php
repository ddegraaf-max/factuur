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
        public string $kind, // response | expired | transferred
        public array $claim,
        public string $responseLabel = '',
        // Bij 'expired': de dag waarop het dossier vanzelf overgaat, of waarom dat niet gebeurt.
        public ?string $autoDate = null,
        public ?string $autoBlocker = null,
    ) {}

    public function envelope(): Envelope
    {
        $invoice = $this->demand->invoice;
        $vars = ['number' => $invoice->number, 'customer' => $invoice->customer_name];

        return new Envelope(
            subject: match ($this->kind) {
                'expired' => __('Termijn verstreken: factuur :number van :customer', $vars),
                'transferred' => __('Overgedragen aan de deurwaarder: factuur :number van :customer', $vars),
                default => __('Reactie op je aanmaning: factuur :number van :customer', $vars),
            },
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
                'autoDate' => $this->autoDate,
                'autoBlocker' => $this->autoBlocker,
                'url' => route('invoices.show', $this->demand->invoice_id),
            ],
        );
    }
}
