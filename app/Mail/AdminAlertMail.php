<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A single admin-facing notice shape — headline, a sentence of context, a few label/value
 * facts and one button — reused for every "something happened, here's what to do next"
 * event on a deal, instead of a Mailable + template per event. Carries only scalars/arrays
 * so it serialises cleanly onto the queue.
 *
 * $tone: info | success | warning | danger (colours the fact box).
 */
class AdminAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<string, string> $facts label => value
     */
    public function __construct(
        public string $alertSubject,
        public string $headline,
        public string $intro,
        public array $facts = [],
        public ?string $ctaUrl = null,
        public ?string $ctaLabel = null,
        public string $tone = 'info',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->alertSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.alert',
            with: [
                'headline' => $this->headline,
                'intro' => $this->intro,
                'facts' => $this->facts,
                'ctaUrl' => $this->ctaUrl,
                'ctaLabel' => $this->ctaLabel,
                'palette' => match ($this->tone) {
                    'success' => ['bg' => '#e8f5e9', 'fg' => '#2e7d32'],
                    'warning' => ['bg' => '#fff3e0', 'fg' => '#e65100'],
                    'danger' => ['bg' => '#fdecea', 'fg' => '#b71c1c'],
                    default => ['bg' => '#e7f0ff', 'fg' => '#1a4b8c'],
                },
            ],
        );
    }
}
