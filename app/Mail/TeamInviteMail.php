<?php

namespace App\Mail;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Uitnodiging om als teamlid mee te werken in een EasyInvoice-omgeving. */
class TeamInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invitation $invitation,
        public string $inviterName,
    ) {}

    public function envelope(): Envelope
    {
        $company = $this->invitation->company?->name ?? 'EasyInvoice';

        return new Envelope(
            subject: "{$this->inviterName} nodigt je uit voor {$company} op EasyInvoice",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.team-invite',
            with: [
                'inviter' => $this->inviterName,
                'company' => $this->invitation->company?->name ?? 'het bedrijf',
                'roleLabel' => User::ROLE_LABELS[$this->invitation->role] ?? $this->invitation->role,
                'url' => route('invitation.show', $this->invitation->token),
                'expires' => $this->invitation->expires_at->translatedFormat('j F Y'),
            ],
        );
    }
}
