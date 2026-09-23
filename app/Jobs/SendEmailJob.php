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
use Throwable;

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
            Notification::where('id', $this->notificationId)->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }
    }

    /**
     * Retries exhausted — this is the only place a permanent send failure is
     * actually recorded anywhere; without this the Notification row would sit
     * at 'queued' forever with no trace beyond Laravel's generic failed_jobs table.
     */
    public function failed(Throwable $exception): void
    {
        if ($this->notificationId) {
            Notification::where('id', $this->notificationId)->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
