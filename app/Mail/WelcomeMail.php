<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name', 'Foxiqo') . '!',
        );
    }

    public function content(): Content
    {
        $dashboardRoute = $this->user->isAdmin() ? 'admin.dashboard' : 'customer.dashboard';

        return new Content(
            view: 'emails.welcome',
            with: [
                'user' => $this->user,
                'company' => $this->user->company,
                'dashboardUrl' => route($dashboardRoute),
                'loginUrl' => route('login'),
            ],
        );
    }
}
