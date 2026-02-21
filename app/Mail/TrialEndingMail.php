<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEndingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscription $subscription) {}

    public function envelope(): Envelope
    {
        $daysLeft = $this->subscription->trialDaysRemaining();
        return new Envelope(
            subject: "Your Trial Ends in {$daysLeft} Day(s): {$this->subscription->agent->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-ending',
            with: [
                'subscription' => $this->subscription,
                'company'      => $this->subscription->company,
                'agent'        => $this->subscription->agent,
                'plan'         => $this->subscription->plan,
                'daysLeft'     => $this->subscription->trialDaysRemaining(),
            ],
        );
    }
}
