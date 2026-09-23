<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WebhookLog;
use App\Services\AuditService;
use App\Services\EmailService;
use App\Services\InvoiceService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaddleWebhookController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected EmailService $emailService,
        protected InvoiceService $invoiceService,
        protected SubscriptionService $subscriptionService
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
        } catch (\Exception $e) {
            $webhookLog->markFailed($e->getMessage());
        }

        // Always 200 — Paddle retries on anything else, and a failed row is already
        // logged above for follow-up.
        return response('OK', 200);
    }

    protected function processWebhook(WebhookLog $webhookLog): void
    {
        $payload = $webhookLog->payload;
        $eventType = $payload['event_type'] ?? null;

        match ($eventType) {
            'transaction.completed' => $this->handleTransactionCompleted($payload),
            'transaction.payment_failed' => $this->handleTransactionPaymentFailed($payload),
            'transaction.updated', 'transaction.canceled', 'transaction.past_due' => $this->syncPaddleTransactionStatus($payload),
            'adjustment.created', 'adjustment.updated' => $this->handleAdjustment($payload),
            default => null,
        };
    }

    /**
     * A closer's deal has been paid. Provision what can be automated right now:
     * the Company and the client's first (customer) User, invited through the
     * existing signup-token flow. Attaching a real Agent/Subscription stays a
     * manual admin step once the Retell agent actually exists — see
     * SubscriptionService::create()'s $alreadyPaid parameter for how that step
     * picks up the payment already collected here.
     *
     * If this transaction isn't a deal's original payment, it's checked against
     * every Subscription we've linked to a Paddle subscription — see
     * handleSubscriptionRenewalPayment() for what happens then.
     */
    protected function handleTransactionCompleted(array $payload): void
    {
        $transactionId = $payload['data']['id'] ?? null;

        if (!$transactionId) {
            throw new \Exception('Missing transaction id in transaction.completed payload');
        }

        $deal = Deal::where('paddle_transaction_id', $transactionId)->first();

        if (!$deal) {
            $paddleSubscriptionId = $payload['data']['subscription_id'] ?? null;

            if ($paddleSubscriptionId) {
                $this->handleSubscriptionRenewalPayment($payload, $paddleSubscriptionId);
            }

            return;
        }

        if ($deal->status === 'paid') {
            return; // Already processed, idempotent
        }

        $paddleCustomerId = $payload['data']['customer_id'] ?? null;

        $company = $deal->company ?: Company::firstOrCreate(
            ['email' => $deal->email],
            [
                'name' => $deal->business_name,
                'phone' => $deal->phone,
                'city' => $deal->city,
                'country' => $deal->country,
                'status' => 'active',
                'paddle_customer_id' => $paddleCustomerId,
            ]
        );

        if ($paddleCustomerId && !$company->paddle_customer_id) {
            $company->update(['paddle_customer_id' => $paddleCustomerId]);
        }

        $user = User::where('email', $deal->email)->first();

        if (!$user) {
            [$firstName, $lastName] = $this->splitName($deal->customer_name);

            $user = User::create([
                'company_id' => $company->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $deal->email,
                'phone' => $deal->phone,
                'role' => 'customer',
                'status' => 'pending',
            ]);

            $token = $user->generateSignupToken();

            $this->emailService->sendUserInvitation($user, $token);
        }

        $deal->update([
            'status' => 'paid',
            'company_id' => $company->id,
            'paddle_subscription_id' => $payload['data']['subscription_id'] ?? null,
        ]);

        $this->auditService->log('deal_paid', $deal);

        // The activation fee is real money Paddle just collected but — unlike the
        // monthly retainer — has no Subscription to attach an invoice to later, so
        // it's recorded here or not at all. Skipped when waived (activation_price
        // 0, e.g. an overridden deal).
        if ((float) $deal->activation_price > 0) {
            $activationInvoice = $this->invoiceService->createStandaloneInvoice(
                $company->id,
                'activation',
                (float) $deal->activation_price,
                $transactionId,
                $payload['data']['status'] ?? null
            );

            $this->invoiceService->markAsPaid($activationInvoice, 'paddle', $transactionId);
        }
    }

    /**
     * Paddle just successfully charged a subscription we've linked (via
     * Subscription::paddle_subscription_id, set when the Subscription was created
     * from a Deal — see SubscriptionController::store()). This fires for every
     * renewal after the first, since a renewal transaction has its own id and
     * never matches a Deal directly.
     *
     * Only 'subscription'-type invoices are ever matched here — Paddle's fixed
     * recurring price only ever covers the retainer, never the separately-billed
     * usage invoices (see InvoiceService::createUsageInvoice()).
     */
    protected function handleSubscriptionRenewalPayment(array $payload, string $paddleSubscriptionId): void
    {
        $subscription = Subscription::where('paddle_subscription_id', $paddleSubscriptionId)->first();

        if (!$subscription) {
            // Not one of ours, or the Subscription hasn't been created yet for a
            // trial deal whose Paddle trial already ended — nothing to reconcile.
            return;
        }

        $transactionId = $payload['data']['id'] ?? null;

        if (Invoice::where('paddle_transaction_id', $transactionId)->exists()) {
            return; // Already recorded this exact charge, idempotent
        }

        // Still marked trial on our side means this is Paddle's first real charge
        // after its own trial period elapsed — there's no invoice yet to reconcile
        // against, this transaction IS what starts the first paid period.
        if ($subscription->is_trial) {
            $this->convertPaddleTrialToPaidPeriod($subscription, $payload, $transactionId);
            return;
        }

        $invoice = $subscription->invoices()
            ->where('invoice_type', 'subscription')
            ->where('status', '!=', 'paid')
            ->oldest('billing_period_start')
            ->first();

        if (!$invoice) {
            Log::warning("Paddle renewal payment for subscription {$subscription->uuid} (paddle_subscription_id={$paddleSubscriptionId}) has no matching unpaid invoice to reconcile.", [
                'transaction_id' => $transactionId,
            ]);
            return;
        }

        $invoice->update(['paddle_transaction_id' => $transactionId]);
        $this->invoiceService->markAsPaid($invoice, 'paddle', $transactionId);

        $this->auditService->log('paddle_renewal_reconciled', $subscription, [
            'invoice_id' => $invoice->id,
            'paddle_transaction_id' => $transactionId,
        ]);
    }

    /**
     * Paddle's own trial just ended and its first real charge succeeded. This is
     * this subscription's equivalent of SubscriptionService::expireTrial(), except
     * Paddle already collected the payment instead of it going out through Nsave —
     * snapshot the trial period's usage, roll the subscription into its first real
     * (Paddle) billing period, and record that period's invoice as already paid.
     */
    protected function convertPaddleTrialToPaidPeriod(Subscription $subscription, array $payload, ?string $transactionId): void
    {
        $this->subscriptionService->createBillingCycleSnapshot($subscription);

        $billingPeriod = $payload['data']['billing_period'] ?? null;
        $periodStart = $billingPeriod['starts_at'] ?? null ? Carbon::parse($billingPeriod['starts_at']) : Carbon::today();
        $periodEnd = $billingPeriod['ends_at'] ?? null ? Carbon::parse($billingPeriod['ends_at']) : $periodStart->copy()->addDays(30);

        $subscription->update([
            'is_trial' => false,
            'trial_ends_at' => null,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'minutes_used' => 0,
            'circuit_breaker_triggered' => false,
            'circuit_breaker_triggered_at' => null,
            'expires_at' => $periodEnd->copy()->endOfDay(),
        ]);

        $invoice = $this->invoiceService->createForSubscription($subscription);
        $invoice->update(['paddle_transaction_id' => $transactionId]);
        $this->invoiceService->markAsPaid($invoice, 'paddle', $transactionId);

        $this->auditService->log('paddle_trial_converted', $subscription, [
            'invoice_id' => $invoice->id,
            'paddle_transaction_id' => $transactionId,
        ]);
    }

    protected function handleTransactionPaymentFailed(array $payload): void
    {
        $transactionId = $payload['data']['id'] ?? null;

        if (!$transactionId) {
            return;
        }

        $deal = Deal::where('paddle_transaction_id', $transactionId)
            ->where('status', 'sent')
            ->first();

        if ($deal) {
            $deal->update(['status' => 'expired']);
            return;
        }

        // Not the original deal payment — a renewal charge failed instead. Nothing
        // to expire, but this needs to be visible: sync the failing invoice's
        // paddle_status and leave an audit trail entry so it shows up in that
        // invoice/subscription's activity timeline.
        $paddleSubscriptionId = $payload['data']['subscription_id'] ?? null;
        $subscription = $paddleSubscriptionId
            ? Subscription::where('paddle_subscription_id', $paddleSubscriptionId)->first()
            : null;

        if (!$subscription) {
            return;
        }

        $invoice = $subscription->invoices()
            ->where('invoice_type', 'subscription')
            ->where('status', '!=', 'paid')
            ->oldest('billing_period_start')
            ->first();

        if ($invoice) {
            $invoice->update(['paddle_status' => $payload['data']['status'] ?? 'payment_failed']);
        }

        $this->auditService->log('paddle_payment_failed', $subscription, [
            'invoice_id' => $invoice?->id,
            'paddle_transaction_id' => $transactionId,
        ]);
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
     * A refund, credit, or chargeback happened in Paddle against a transaction we
     * have an invoice for. Always logged for visibility the moment Paddle reports
     * it; the invoice's own status only flips to 'refunded' once the adjustment is
     * actually approved (a pending_approval refund hasn't moved any money yet).
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

        foreach ($invoices as $invoice) {
            $this->auditService->log('paddle_adjustment', $invoice, [
                'action' => $action,
                'status' => $status,
                'adjustment_id' => $payload['data']['id'] ?? null,
            ]);

            if ($status === 'approved' && in_array($action, ['refund', 'chargeback'], true)) {
                $invoice->update(['status' => 'refunded', 'paddle_status' => $status]);
            }
        }
    }

    protected function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return [$parts[0] ?? $fullName, $parts[1] ?? ''];
    }
}
