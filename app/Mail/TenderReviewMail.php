<?php

namespace App\Mail;

use App\Models\TenderRequest;
use App\Services\TenderReviewService;
use App\Support\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aan de ondernemer: de offertecheck van een binnengekomen prijsopgave —
 * het oordeel, waarom, wat er wel en niet in zit, en de vragen die erbij horen.
 */
class TenderReviewMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array<string, mixed>  $review */
    public function __construct(
        public TenderRequest $request,
        public array $review,
    ) {}

    public function envelope(): Envelope
    {
        $subject = __('Prijsopgave :name voor :package: :verdict', [
            'name' => $this->request->subcontractor?->name,
            'package' => $this->request->round?->title,
            'verdict' => TenderReviewService::verdictLabel($this->review['verdict'] ?? 'unclear'),
        ]);

        return new Envelope(subject: $subject . ' — ' . Brand::name());
    }

    public function content(): Content
    {
        $round = $this->request->round;

        return new Content(
            view: 'emails.tender-review',
            with: [
                'request' => $this->request,
                'round' => $round,
                'company' => $round->company,
                'r' => $this->review,
                'verdictLabel' => TenderReviewService::verdictLabel($this->review['verdict'] ?? 'unclear'),
                'url' => route('tenders.show', $round->id),
            ],
        );
    }
}
