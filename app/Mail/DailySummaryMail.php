<?php

namespace App\Mail;

use App\Models\Company;
use App\Support\Brand;
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
            ? trans_choice(':count factuur vervallen — je dagoverzicht|:count facturen vervallen — je dagoverzicht', $overdue, ['count' => $overdue])
            : __('Je dagoverzicht van :brand', ['brand' => Brand::name()]);

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
