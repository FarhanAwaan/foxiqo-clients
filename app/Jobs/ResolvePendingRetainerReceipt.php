<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\PaddleLifecycleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * The timeout half of the combined retainer+usage receipt (see PaddleLifecycleService::
 * resolveRetainerReceipt()). Dispatched with a short delay the moment a retainer charge's usage
 * sibling is sent to Paddle; by the time this actually runs, one of three things is already true:
 *  - the usage charge settled paid → applyUsageCharge() already resolved and sent the combined
 *    receipt itself, clearing the marker, so this is a no-op;
 *  - the usage charge was declined → applyUsageChargeFailure() already resolved it (retainer receipt
 *    sent alone), also a no-op here;
 *  - neither has happened yet (a slow webhook, or one that never arrives) → THIS call is what finally
 *    sends the retainer's own receipt alone, rather than holding it hostage to a usage charge that
 *    may never resolve. The usage charge, whenever it does resolve, sends its own separate receipt.
 */
class ResolvePendingRetainerReceipt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Invoice $retainerInvoice) {}

    public function handle(PaddleLifecycleService $lifecycle): void
    {
        $lifecycle->resolveRetainerReceipt($this->retainerInvoice->id);
    }
}
