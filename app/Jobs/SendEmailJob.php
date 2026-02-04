<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Mailable $mailable,
        public string $recipientEmail,
        public ?int $notificationId = null
    ) {}

    public function handle(): void
    {
        Mail::to($this->recipientEmail)->send($this->mailable);

        if ($this->notificationId) {
            Notification::where('id', $this->notificationId)->update(['sent_at' => now()]);
        }
    }
}
