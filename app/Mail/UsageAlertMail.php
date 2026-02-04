<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UsageAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Usage Alert: {$this->subscription->agent->name} — {$this->subscription->company->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.usage-alert',
            with: [
                'subscription' => $this->subscription,
                'company' => $this->subscription->company,
                'agent' => $this->subscription->agent,
                'plan' => $this->subscription->plan,
                'usagePercentage' => $this->subscription->getUsagePercentage(),
            ],
        );
    }
}
