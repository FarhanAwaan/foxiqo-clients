<?php

namespace App\Jobs;

use App\Exceptions\WebhookOutOfOrderException;
use App\Models\WebhookLog;
use App\Services\RetellService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRetellWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    // Tracks how many times this job was re-queued due to ordering issues
    public int $reorderAttempts = 0;

    // Maximum re-queues before giving up (4 × 5s = 20s max wait)
    protected const MAX_REORDER_ATTEMPTS = 4;

    public function __construct(public WebhookLog $webhookLog) {}

    public function handle(RetellService $retellService): void
    {
        try {
            $retellService->processWebhook($this->webhookLog);
        } catch (WebhookOutOfOrderException $e) {
            if ($this->reorderAttempts >= self::MAX_REORDER_ATTEMPTS) {
                // call_ended never arrived after 60s — mark as failed and give up
                $this->webhookLog->markFailed(
                    "Processed out-of-order after {$this->reorderAttempts} retries: {$e->getMessage()}"
                );
                return;
            }

            $this->reorderAttempts++;
            $this->release(5); // wait 5 seconds, then retry
        } catch (\Exception $e) {
            $this->webhookLog->markFailed($e->getMessage());
            throw $e;
        }
    }
}
