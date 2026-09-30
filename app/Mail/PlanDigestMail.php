<?php

namespace App\Mail;

use App\Models\Company;
use App\Support\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Maandagochtend: wat er deze en volgende week start op de projecten, wat
 * nog ingepland moet worden, en wie een probleem meldde of nog moet antwoorden.
 */
class PlanDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array<string, mixed>  $data */
    public function __construct(
        public Company $company,
        public array $data,
    ) {}

    public function envelope(): Envelope
    {
        $n = count($this->data['this_week']);
        $subject = $n > 0
            ? trans_choice('Week :week: :count onderdeel start — je projectplanning|Week :week: :count onderdelen starten — je projectplanning', $n, ['week' => $this->data['week'], 'count' => $n])
            : __('Week :week — je projectplanning', ['week' => $this->data['week']]);

        return new Envelope(subject: $subject . ' — ' . Brand::name());
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.plan-digest',
            with: ['company' => $this->company, 'd' => $this->data],
        );
    }
}
