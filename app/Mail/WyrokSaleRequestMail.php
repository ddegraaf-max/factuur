<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aanmelding van een oud vonnis / tytuł wykonawczy (skup wyroków) bij
 * sprzedamfakture.pl — vanuit de app (met bedrijfscontext) of via het
 * publieke formulier op /skup-wyrokow (lead).
 */
class WyrokSaleRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string, mixed> $data gevalideerde formuliervelden */
    public function __construct(
        public array $data,
        public ?User $user = null,
        public ?Company $company = null,
    ) {}

    public function envelope(): Envelope
    {
        $from = $this->company?->name ?: ($this->data['firm'] ?? null) ?: ($this->data['name'] ?? '');
        $replyEmail = $this->user?->email ?: (string) ($this->data['email'] ?? '');
        $replyName = $this->user?->name ?: (string) ($this->data['name'] ?? '');

        return new Envelope(
            subject: 'Skup wyroku — ' . ($this->data['sygnatura'] ?? '') . ($from ? ' — ' . $from : ''),
            replyTo: $replyEmail ? [new Address($replyEmail, $replyName ?: null)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.wyrok-request', with: [
            'data' => $this->data,
            'user' => $this->user,
            'company' => $this->company,
            'formaLabels' => [
                'sp_zoo' => 'Sp. z o.o.',
                'sa' => 'S.A.',
                'jdg' => 'Jednoosobowa działalność (JDG)',
                'inna' => 'Inna / nie wiem',
            ],
            'egzekucjaLabels' => [
                'none' => 'Nigdy nie prowadzona',
                'bezskutecznosc' => 'Umorzona — bezskuteczność',
                'inna' => 'Umorzona — inny powód',
                'nie_wiem' => 'Nie wiem',
            ],
        ]);
    }
}
