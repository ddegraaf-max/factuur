<?php

namespace App\Mail;

use App\Models\PaymentDemand;
use App\Services\PublicDemandService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail aan de schuldeiser bij een online aanmaning zonder account: eerst de
 * link om zijn e-mailadres te bevestigen (confirm), en na de bevestiging de
 * link voor de klant met zijn eigen link om mee te kijken (ready).
 */
class PublicDemandMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array<string, mixed>  $claim  de vordering van vandaag */
    public function __construct(
        public PaymentDemand $demand,
        public string $kind, // confirm | ready
        public array $claim,
        // Voor de voorbeeldpagina: alle links wijzen dan naar het voorbeeld.
        public ?string $exampleUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        $vars = ['number' => $this->demand->invoice_number];

        return new Envelope(
            subject: $this->kind === 'confirm'
                ? __('Bevestig je aanmaning: factuur :number', $vars)
                : __('Je aanmaning staat online: factuur :number', $vars),
        );
    }

    public function content(): Content
    {
        $demand = $this->demand;

        return new Content(
            view: 'emails.public-demand',
            with: [
                'kind' => $this->kind,
                'demand' => $demand,
                'claim' => $this->claim,
                'confirmUrl' => $this->exampleUrl ?? $demand->confirmUrl(),
                'debtorUrl' => $this->exampleUrl ?? $demand->url(),
                'creditorUrl' => $this->exampleUrl ?? $demand->creditorUrl(),
                'letterUrl' => $this->exampleUrl ?? route('demand.pdf', ['token' => $demand->token, 'k' => $demand->creditor_key]),
                'facts' => app(PublicDemandService::class)->factsSummary($demand),
                'confirmDays' => PaymentDemand::CONFIRM_DAYS,
            ],
        );
    }
}
