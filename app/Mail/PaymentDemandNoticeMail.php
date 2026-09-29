<?php

namespace App\Mail;

use App\Models\PaymentDemand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Bericht aan de schuldeiser over zijn online aanmaning: de klant heeft
 * gereageerd (response), de termijn is voorbij zonder betaling (expired), het
 * dossier is automatisch overgedragen (transferred) of hij heeft het zelf
 * overgedragen vanaf een aanmaning zonder account (handed).
 */
class PaymentDemandNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array<string, mixed>  $claim  de vordering van vandaag */
    public function __construct(
        public PaymentDemand $demand,
        public string $kind, // response | expired | transferred | handed
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
                'transferred', 'handed' => __('Overgedragen aan de deurwaarder: factuur :number van :customer', $vars),
                default => __('Reactie op je aanmaning: factuur :number van :customer', $vars),
            },
        );
    }

    public function content(): Content
    {
        $standalone = $this->demand->isStandalone();

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
                'standalone' => $standalone,
                // Zonder account is er geen factuurpagina: dan naar het eigen overzicht van de schuldeiser.
                'url' => $standalone ? $this->demand->creditorUrl() : route('invoices.show', $this->demand->invoice_id),
            ],
        );
    }
}
