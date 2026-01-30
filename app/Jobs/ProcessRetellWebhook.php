<?php

namespace App\Jobs;

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

    public function __construct(public WebhookLog $webhookLog) {}

    public function handle(RetellService $retellService): void
    {
        try {
            $retellService->processWebhook($this->webhookLog);
        } catch (\Exception $e) {
            $this->webhookLog->markFailed($e->getMessage());
            throw $e;
        }
    }
}
