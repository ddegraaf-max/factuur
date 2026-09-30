<?php

namespace App\Mail;

use App\Models\ProjectPlanItem;
use App\Support\Sender;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail aan een onderaannemer over de planning van zijn onderdeel: de
 * vooraankondiging een week vooraf, de herinnering als de week aanbreekt, of
 * de vraag of hij eerder kan beginnen. Afzender is de ondernemer.
 */
class PlanMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProjectPlanItem $item,
        public string $kind = 'reminder', // headsup | reminder | earlier
    ) {}

    public function envelope(): Envelope
    {
        $company = $this->item->project->company;
        $vars = [
            'title' => $this->item->title,
            'company' => $company->name,
            'date' => $this->item->starts_on?->translatedFormat('j F'),
            'proposed' => $this->item->request_start?->translatedFormat('j F'),
        ];
        $subject = match ($this->kind) {
            'headsup' => __('Volgende week: :title — start :date', $vars),
            'earlier' => __('Kunt u eerder beginnen met :title? Voorstel: :proposed', $vars),
            default => __('Deze week: :title — start :date', $vars),
        };

        return new Envelope(
            from: Sender::address($company, $company->name ?: config('mail.from.name')),
            replyTo: array_filter([$company->email ? new Address($company->email, $company->name ?: null) : null]),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $item = $this->item;
        $project = $item->project;

        return new Content(
            view: 'emails.plan',
            text: 'emails.plan-text',
            with: [
                'kind' => $this->kind,
                'item' => $item,
                'project' => $project,
                'company' => $project->company,
                'subcontractor' => $item->subcontractor,
                'url' => $item->url(),
                'location' => $project->location ?: $item->round?->location,
            ],
        );
    }
}
