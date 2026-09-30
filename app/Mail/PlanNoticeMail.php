<?php

namespace App\Mail;

use App\Models\ProjectPlanItem;
use App\Support\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Bericht aan de ondernemer: een onderaannemer heeft op de planning
 * gereageerd (eerder beginnen: ja, andere dag of nee) of meldt een probleem.
 */
class PlanNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProjectPlanItem $item,
        public string $kind = 'answer', // answer | problem
    ) {}

    public function envelope(): Envelope
    {
        $vars = ['name' => $this->item->subcontractor?->name, 'title' => $this->item->title];
        $subject = match (true) {
            $this->kind === 'problem' => __(':name meldt een probleem met :title', $vars),
            $this->item->request_answer === 'accepted' => __(':name kan eerder beginnen met :title', $vars),
            $this->item->request_answer === 'counter' => __(':name stelt een andere dag voor voor :title', $vars),
            default => __(':name kan niet eerder beginnen met :title', $vars),
        };

        return new Envelope(subject: $subject . ' — ' . Brand::name());
    }

    public function content(): Content
    {
        $project = $this->item->project;

        return new Content(
            view: 'emails.plan-notice',
            with: [
                'kind' => $this->kind,
                'item' => $this->item,
                'project' => $project,
                'company' => $project->company,
                'url' => route('projects.show', $project->id) . '#planning',
            ],
        );
    }
}
