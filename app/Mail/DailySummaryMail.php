<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailySummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public array $summary,
    ) {}

    public function envelope(): Envelope
    {
        $overdue = $this->summary['overdue']['count'] ?? 0;

        $subject = $overdue > 0
            ? sprintf('%d factu%s vervallen — je dagoverzicht', $overdue, $overdue === 1 ? 'ur' : 'ren')
            : 'Je dagoverzicht van EasyInvoice';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-summary',
            with: [
                'company' => $this->company,
                's' => $this->summary,
            ],
        );
    }
}
