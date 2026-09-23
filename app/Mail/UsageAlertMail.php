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
        $plan = $this->subscription->plan;
        $usageCost = $this->subscription->minutes_used * (float) ($plan->per_minute_rate ?? 0);

        return new Content(
            view: 'emails.admin.usage-alert',
            with: [
                'subscription' => $this->subscription,
                'company' => $this->subscription->company,
                'agent' => $this->subscription->agent,
                'plan' => $plan,
                'usageCost' => $usageCost,
                'predictedTotal' => (float) $plan->price + $usageCost,
            ],
        );
    }
}
