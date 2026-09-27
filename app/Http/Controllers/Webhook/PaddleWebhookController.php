<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\WebhookLog;
use App\Services\AuditService;
use App\Services\PaddleLifecycleService;
use App\Support\PaddleMoney;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Receives Paddle's webhooks. Deliberately thin: the domain logic (provisioning a paid deal,
 * recording charges, mirroring the subscription, notifying) lives in PaddleLifecycleService,
 * shared with the `paddle:reconcile` poll and the admin actions so all three behave identically.
 */
class PaddleWebhookController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected PaddleLifecycleService $lifecycle
    ) {}

    public function handle(Request $request): Response
    {
        $webhookLog = WebhookLog::create([
            'source' => 'paddle',
            'event_type' => $request->input('event_type', 'unknown'),
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
            'status' => 'received',
        ]);

        try {
            $this->processWebhook($webhookLog);
            $webhookLog->markProcessed();
        } catch (\Throwable $e) {
            // Throwable, not Exception: a TypeError or a PHP warning promoted to an error is not an
            // Exception, and letting one escape would answer Paddle with a 500 and a log row stuck at 'received'.
            $webhookLog->markFailed($e->getMessage());
        }

        // Always 200 — Paddle retries on anything else, and a failed row is already
        // logged above for follow-up (and `paddle:reconcile` re-derives the state anyway).
        return response('OK', 200);
    }

    protected function processWebhook(WebhookLog $webhookLog): void
    {
        $payload = $webhookLog->payload;
        $eventType = $payload['event_type'] ?? null;
        $data = $payload['data'] ?? [];

        match ($eventType) {
            'transaction.completed' => $this->lifecycle->processCompletedTransaction($data),
            'transaction.payment_failed' => $this->lifecycle->processPaymentFailed($data),
            'transaction.updated', 'transaction.canceled', 'transaction.past_due' => $this->syncPaddleTransactionStatus($payload),
            'subscription.created', 'subscription.trialing', 'subscription.activated', 'subscription.updated',
            'subscription.canceled', 'subscription.paused', 'subscription.resumed', 'subscription.past_due' => $this->lifecycle->syncSubscription($data, 'paddle'),
            'adjustment.created', 'adjustment.updated' => $this->handleAdjustment($payload),
            default => null,
        };
    }

    /**
     * Keeps Invoice::paddle_status in sync with Paddle's own transaction lifecycle
     * (billed/completed/canceled/past_due/...) — separate from our own workflow
     * `status`, which our own code drives. This is purely visibility; it never
     * changes our `status`, `sent_at` or `paid_at`.
     */
    protected function syncPaddleTransactionStatus(array $payload): void
    {
        $transactionId = $payload['data']['id'] ?? null;
        $status = $payload['data']['status'] ?? null;

        if (!$transactionId || !$status) {
            return;
        }

        Invoice::where('paddle_transaction_id', $transactionId)->update(['paddle_status' => $status]);
    }

    /**
     * A refund, credit, or chargeback happened in Paddle against a transaction we have an invoice
     * for. Always logged for visibility the moment Paddle reports it; the invoice's own status only
     * flips to 'refunded', and paddle_refunded_amount only fills in, once the adjustment is actually
     * approved (a pending_approval refund hasn't moved any money yet).
     */
    protected function handleAdjustment(array $payload): void
    {
        $transactionId = $payload['data']['transaction_id'] ?? null;

        if (!$transactionId) {
            return;
        }

        $invoices = Invoice::where('paddle_transaction_id', $transactionId)->get();

        if ($invoices->isEmpty()) {
            return;
        }

        $action = $payload['data']['action'] ?? null;
        $status = $payload['data']['status'] ?? null;
        $approved = $status === 'approved' && in_array($action, ['refund', 'chargeback'], true);
        $refunded = $approved ? PaddleMoney::refundedAmount($payload['data']['totals'] ?? []) : null;

        // A deal's checkout can charge setup fee + first month in ONE transaction, producing TWO
        // invoices for it (see PaddleLifecycleService::processDealPayment()'s proportional split) — a
        // refund on that transaction is split the same way, by each invoice's own share of what was
        // actually charged, so summing every invoice's paddle_refunded_amount never double-counts one
        // refund.
        $sharedBasis = $invoices->sum(fn ($i) => (float) ($i->paddle_charged_amount ?? $i->amount));

        foreach ($invoices as $invoice) {
            $this->auditService->log('paddle_adjustment', $invoice, [
                'action' => $action,
                'status' => $status,
                'adjustment_id' => $payload['data']['id'] ?? null,
            ]);

            if (!$approved) {
                continue;
            }

            $basis = (float) ($invoice->paddle_charged_amount ?? $invoice->amount);
            $share = $sharedBasis > 0 ? $basis / $sharedBasis : (1 / $invoices->count());

            $invoice->update([
                'status' => 'refunded',
                'paddle_status' => $status,
                'paddle_refunded_amount' => round(((float) ($invoice->paddle_refunded_amount ?? 0)) + ($refunded * $share), 2),
            ]);
        }
    }
}
