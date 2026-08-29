<?php

namespace App\Mail;

use App\Support\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

/** Alarmmail naar de eigenaar bij een onverwachte fout in productie. */
class ErrorAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $trace;

    public function __construct(public Throwable $exception, public array $context = [])
    {
        // Alleen de eerste regels van de stacktrace, zonder vendor-ruis vooraan.
        $this->trace = array_slice(array_values(array_filter(
            explode("\n", $exception->getTraceAsString()),
            fn ($line) => ! str_contains($line, '/vendor/') || str_contains($line, 'App\\')
        )), 0, 12);
    }

    public function envelope(): Envelope
    {
        $where = $this->context['url'] ?? $this->context['console'] ?? 'onbekend';

        return new Envelope(
            subject: '⚠️ ' . Brand::name() . '-fout: ' . class_basename($this->exception) . ' — ' . mb_substr($where, 0, 60),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.error-alert');
    }
}
