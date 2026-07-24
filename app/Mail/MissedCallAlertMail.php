<?php

namespace App\Mail;

use App\Models\CallLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MissedCallAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CallLog $callLog
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Missed Call: {$this->callLog->agent->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.missed-call-alert',
            with: [
                'callLog' => $this->callLog,
                'agent' => $this->callLog->agent,
                'company' => $this->callLog->agent->company,
                'reasonLabel' => $this->reasonLabel(),
            ],
        );
    }

    protected function reasonLabel(): string
    {
        return match ($this->callLog->metadata['disconnection_reason'] ?? null) {
            'dial_no_answer' => 'No answer',
            'dial_busy' => 'Line busy',
            'dial_failed' => 'Call failed to connect',
            'voicemail_reached' => 'Went to voicemail',
            'user_declined' => 'Call declined',
            'error_user_not_joined' => 'Caller never connected',
            default => 'Missed call',
        };
    }
}
