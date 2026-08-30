<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Herinnering aan de ondernemer: het btw-tijdvak is afgesloten en de aangifte
 * (én betaling) moet vóór de deadline binnen zijn. Met de cijfers en de
 * betaalgegevens er meteen bij.
 */
class VatReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public array $period,
        /** Laatste herinnering (enkele dagen voor de deadline). */
        public bool $final = false,
    ) {}

    public function envelope(): Envelope
    {
        $p = $this->period;
        $days = (int) ($p['days_left'] ?? 0);

        $subject = $this->final
            ? trans_choice('Nog :days dag: btw-aangifte :label :year|Nog :days dagen: btw-aangifte :label :year', $days, ['days' => $days, 'label' => $p['label'], 'year' => $p['year']])
            : __('Btw-aangifte :label :year — vóór :deadline', ['label' => $p['label'], 'year' => $p['year'], 'deadline' => $p['deadline_label']]);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vat-reminder',
            with: [
                'company' => $this->company,
                'p' => $this->period,
                'final' => $this->final,
            ],
        );
    }
}
