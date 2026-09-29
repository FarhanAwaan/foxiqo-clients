<?php

namespace App\Services;

use App\Exceptions\PaddleApiException;
use App\Models\SystemSetting;
use App\Support\BillingTime;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;

/**
 * Paddle Billing API client. Mirrors the shape of the (now-retired) PayoneerService:
 * credentials come from SystemSetting, calls go out through Laravel's Http facade —
 * no SDK dependency.
 *
 * Paddle never lets you create a Subscription directly — it creates one automatically
 * once a Transaction with recurring items is paid. See PaddleLifecycleService for the
 * other half of this flow (webhooks + the admin actions built on the calls below).
 */
class PaddleService
{
    protected string $apiKey;
    protected string $environment;
    protected string $productId;

    public function __construct()
    {
        $this->apiKey = SystemSetting::getValue('paddle_api_key', '');
        $this->environment = SystemSetting::getValue('paddle_environment', 'sandbox');
        $this->productId = SystemSetting::getValue('paddle_product_id', '');
    }

    protected function baseUrl(): string
    {
        return $this->environment === 'live'
            ? 'https://api.paddle.com'
            : 'https://sandbox-api.paddle.com';
    }

    protected function client()
    {
        return Http::withToken($this->apiKey)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(20)
            ->baseUrl($this->baseUrl());
    }

    /**
     * One request, with Paddle's own error detail surfaced (it's written for humans,
     * e.g. "Subscription has no payment method on file") instead of a raw body dump.
     *
     * @throws PaddleApiException
     */
    protected function request(string $method, string $path, ?array $payload, string $failMessage): array
    {
        $response = match ($method) {
            'GET' => $this->client()->get($path, $payload ?? []),
            'POST' => $this->client()->post($path, $payload ?? (object) []),
            'PATCH' => $this->client()->patch($path, $payload ?? (object) []),
        };

        if (!$response->successful()) {
            $detail = $response->json('error.detail') ?: $response->body();

            throw new PaddleApiException("{$failMessage}: {$detail}", $response->json('error.code'), $response->status());
        }

        return $response->json();
    }

    /**
     * Find a Paddle customer by email, or create one.
     */
    public function findOrCreateCustomer(string $email, string $name): string
    {
        $existing = $this->client()->get('/customers', ['email' => $email]);

        if ($existing->successful() && !empty($existing->json('data'))) {
            return $existing->json('data.0.id');
        }

        return $this->request('POST', '/customers', [
            'email' => $email,
            'name' => $name,
        ], 'Failed to create Paddle customer')['data']['id'];
    }

    /**
     * Create a transaction for a bespoke, consultation-priced deal: up to two non-catalog
     * price line items against the single catalog product, collection_mode automatic so
     * Paddle returns a checkout.
     *
     *  - $monthlyPrice > 0  → a recurring monthly "retainer" line. $trialDays adds a Paddle
     *    trial_period to it: nothing recurring is due at checkout, and Paddle charges the
     *    card on file itself when the trial ends.
     *  - $activationPrice > 0 → a one-time "setup & activation" line. Paddle has no trial
     *    concept for one-time items, so it always charges immediately at checkout.
     *  - A zero amount is never sent as a line: a $0 recurring price would bill $0 every
     *    month forever, and a $0 one-time line is just noise on the invoice. A setup-only
     *    deal is therefore a one-time transaction with no subscription at all.
     *
     * Each price carries a customer-facing `name` (shown at checkout and on Paddle's
     * invoice). Without it both lines show only the product name — the same text twice.
     * `description` is internal to Paddle and never shown to the customer.
     *
     * @return array{id: string, checkout_url: ?string}
     */
    public function createTransaction(
        string $paddleCustomerId,
        float $monthlyPrice,
        float $activationPrice,
        string $reference,
        ?int $trialDays = null
    ): array {
        if (empty($this->productId)) {
            throw new \Exception('Paddle product ID is not configured (Settings > Paddle Integration).');
        }

        $items = [];

        if ($monthlyPrice > 0) {
            $recurring = [
                'name' => 'Monthly retainer',
                'description' => 'Monthly retainer — portal deal ' . $reference,
                'product_id' => $this->productId,
                'unit_price' => [
                    'amount' => (string) round($monthlyPrice * 100),
                    'currency_code' => 'USD',
                ],
                'billing_cycle' => ['interval' => 'month', 'frequency' => 1],
            ];

            if ($trialDays) {
                $recurring['trial_period'] = ['interval' => 'day', 'frequency' => $trialDays];
            }

            $items[] = ['price' => $recurring, 'quantity' => 1];
        }

        if ($activationPrice > 0) {
            $items[] = [
                'price' => [
                    'name' => 'One-time setup & activation fee',
                    'description' => 'One-time setup & activation fee — portal deal ' . $reference,
                    'product_id' => $this->productId,
                    'unit_price' => [
                        'amount' => (string) round($activationPrice * 100),
                        'currency_code' => 'USD',
                    ],
                ],
                'quantity' => 1,
            ];
        }

        if (empty($items)) {
            throw new \InvalidArgumentException('A deal needs a setup fee, a monthly price, or both.');
        }

        $response = $this->request('POST', '/transactions', [
            'customer_id' => $paddleCustomerId,
            'collection_mode' => 'automatic',
            'items' => $items,
            'custom_data' => ['reference' => $reference],
        ], 'Failed to create Paddle transaction');

        return [
            'id' => $response['data']['id'],
            'checkout_url' => $response['data']['checkout']['url'] ?? null,
        ];
    }

    public function getTransaction(string $transactionId): array
    {
        return $this->request('GET', "/transactions/{$transactionId}", null, 'Failed to fetch Paddle transaction')['data'];
    }

    /**
     * Transactions of one Paddle subscription, newest first. Used to catch a charge whose
     * webhook never arrived.
     */
    public function listSubscriptionTransactions(string $subscriptionId, string $status = 'completed', int $perPage = 10): array
    {
        return $this->request('GET', '/transactions', [
            'subscription_id' => $subscriptionId,
            'status' => $status,
            'order_by' => 'id[DESC]',
            'per_page' => $perPage,
        ], 'Failed to list Paddle transactions')['data'] ?? [];
    }

    /**
     * Paddle's invoice PDF link for a transaction — fetched fresh on every call
     * since Paddle's own link expires after an hour, so nothing here is ever
     * cached or stored. Null for transactions with no invoice (e.g. zero-value).
     */
    public function getTransactionInvoiceUrl(string $transactionId): ?string
    {
        $response = $this->client()->get("/transactions/{$transactionId}/invoice", [
            'disposition' => 'inline',
        ]);

        if (!$response->successful()) {
            return null;
        }

        return $response->json('data.url');
    }

    // ──────────────────────────────────────────────
    // Notifications — webhook delivery health
    // ──────────────────────────────────────────────

    /**
     * The most recent webhook deliveries Paddle gave up retrying (any destination, any event
     * type), newest first. This is a DELIVERY problem (wrong signing secret, endpoint down, a
     * deploy that 500s the route) — distinct from an event's own content failing to process,
     * which never shows up here. `status: 'failed'` is passed as a query hint, but the caller
     * should still filter the result defensively: undocumented whether Paddle honors it server
     * side, and trusting an unverified filter silently is how the 2026-09-29 incident would have
     * kept slipping past this exact check.
     */
    public function listFailedNotifications(int $limit = 50): array
    {
        return $this->request('GET', '/notifications', [
            'status' => 'failed',
            'per_page' => $limit,
        ], 'Failed to list Paddle notifications')['data'] ?? [];
    }

    /**
     * Recent webhook deliveries regardless of status — unlike listFailedNotifications(), this
     * also includes ones Paddle considers successfully "delivered". Needed because something
     * sitting in front of this app (a hosting firewall, a WAF) can answer a webhook request
     * with its own 2xx before the request ever reaches Laravel; Paddle has no way to tell that
     * apart from a real success, so a hijacked delivery never shows up as "failed" — the only
     * way to catch it is to inspect what was actually in the response, via getNotificationLogs().
     */
    public function listRecentNotifications(int $limit = 50): array
    {
        return $this->request('GET', '/notifications', [
            'per_page' => $limit,
        ], 'Failed to list Paddle notifications')['data'] ?? [];
    }

    /**
     * The delivery attempts Paddle made for one notification — response code/body per attempt.
     */
    public function getNotificationLogs(string $notificationId): array
    {
        return $this->request('GET', "/notifications/{$notificationId}/logs", null, 'Failed to fetch Paddle notification logs')['data'] ?? [];
    }

    // ──────────────────────────────────────────────
    // Subscriptions — read + the admin lifecycle actions
    // ──────────────────────────────────────────────

    public function getSubscription(string $subscriptionId): array
    {
        return $this->request('GET', "/subscriptions/{$subscriptionId}", null, 'Failed to fetch Paddle subscription')['data'];
    }

    /**
     * Move a TRIALING subscription's first charge date (extend or shorten the trial).
     * Paddle rejects anything less than 30 minutes ahead, and only allows
     * proration_billing_mode do_not_bill for a trial. Returns the updated subscription.
     */
    public function setNextBilledAt(string $subscriptionId, CarbonInterface $at): array
    {
        return $this->request('PATCH', "/subscriptions/{$subscriptionId}", [
            'next_billed_at' => BillingTime::toPaddle($at),
            'proration_billing_mode' => 'do_not_bill',
        ], 'Paddle could not change the charge date')['data'];
    }

    /**
     * End a trial now: Paddle charges the card on file immediately and bills monthly
     * from today. Returns the updated subscription.
     */
    public function activateSubscription(string $subscriptionId): array
    {
        return $this->request('POST', "/subscriptions/{$subscriptionId}/activate", null, 'Paddle could not start billing')['data'];
    }

    /**
     * Cancel a subscription. Scheduled (`next_billing_period`) keeps it running until the
     * trial / paid period ends and then cancels without charging; immediate cancels now.
     * Returns the updated subscription (with scheduled_change set for a scheduled cancel).
     */
    public function cancelSubscription(string $subscriptionId, bool $immediately): array
    {
        return $this->request('POST', "/subscriptions/{$subscriptionId}/cancel", [
            'effective_from' => $immediately ? 'immediately' : 'next_billing_period',
        ], 'Paddle could not cancel the subscription')['data'];
    }

    /**
     * Withdraw a scheduled cancellation so the subscription carries on as normal.
     */
    public function removeScheduledChange(string $subscriptionId): array
    {
        return $this->request('PATCH', "/subscriptions/{$subscriptionId}", [
            'scheduled_change' => null,
        ], 'Paddle could not undo the scheduled change')['data'];
    }

    /**
     * Charge the card already on file for a subscription, right now, for a one-off amount that
     * isn't part of its recurring price — the metered usage overage for a period that just closed.
     * Paddle bills it as its own transaction (origin `subscription_charge`), collected via the
     * same webhook/reconcile path as a renewal — see PaddleLifecycleService::applyUsageCharge().
     *
     * This endpoint's response is the SUBSCRIPTION, not the new transaction (Paddle: "one-time
     * charges aren't held against the subscription entity, so the charges billed aren't returned
     * in the response") — there is nothing to read a transaction id from here; the caller finds
     * out what happened from the webhook/poll like any other charge.
     *
     * Paddle refuses a charge below its own minimum (observed: $0.70 USD) with
     * subscription_update_transaction_balance_less_than_charge_limit — the caller checks the
     * amount against config('billing.paddle_minimum_charge_cents') before calling this, but a real
     * PaddleApiException with that code is still possible (a currency Paddle prices differently)
     * and the caller must handle it, not just card declines.
     *
     * @throws PaddleApiException
     */
    public function createOneTimeCharge(string $subscriptionId, float $amount, string $name, string $description): void
    {
        $this->request('POST', "/subscriptions/{$subscriptionId}/charge", [
            'effective_from' => 'immediately',
            'items' => [[
                'quantity' => 1,
                'price' => [
                    'name' => $name,
                    'description' => $description,
                    'product_id' => $this->productId,
                    'unit_price' => [
                        'amount' => (string) round($amount * 100),
                        'currency_code' => 'USD',
                    ],
                ],
            ]],
        ], 'Paddle could not charge the usage amount');
    }
}
