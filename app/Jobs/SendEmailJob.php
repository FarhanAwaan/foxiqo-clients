<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Support\MessageCopy;
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
        $sent = Mail::to($this->recipientEmail)->send($this->mailable);

        $notification = $this->notificationId ? Notification::find($this->notificationId) : null;

        if ($notification) {
            $update = ['status' => 'sent', 'sent_at' => now()];

            // Keep a copy of what was actually sent for Admin → Emails. Never allowed to turn a
            // delivered email into a failure: the send already happened.
            try {
                $update += MessageCopy::fromSent($sent, $notification->keepsContent());
            } catch (Throwable $e) {
                report($e);
            }

            $notification->update($update);
        }
    }

    /**
     * Retries exhausted — this is the only place a permanent send failure is
     * actually recorded anywhere; without this the Notification row would sit
     * at 'queued' forever with no trace beyond Laravel's generic failed_jobs table.
     */
    public function failed(Throwable $exception): void
    {
        $notification = $this->notificationId ? Notification::find($this->notificationId) : null;

        if (!$notification) {
            return;
        }

        $update = ['status' => 'failed', 'error' => $exception->getMessage()];

        // The email never left, but what it WOULD have said is exactly what someone debugging the
        // failure wants to see — render it now so the panel isn't empty.
        if ($notification->keepsContent()) {
            try {
                $update['html_body'] = $this->mailable->render();
            } catch (Throwable $e) {
                // Rendering can fail for the same reason sending did; the error message above stands.
            }
        }

        $notification->update($update);
    }
}
