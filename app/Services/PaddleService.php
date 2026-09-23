<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;

/**
 * Paddle Billing API client. Mirrors the shape of the (now-retired) PayoneerService:
 * credentials come from SystemSetting, calls go out through Laravel's Http facade —
 * no SDK dependency.
 *
 * Paddle never lets you create a Subscription directly — it creates one automatically
 * once a Transaction with recurring items is paid. See PaddleWebhookController for the
 * other half of this flow.
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
            ->baseUrl($this->baseUrl());
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

        $response = $this->client()->post('/customers', [
            'email' => $email,
            'name' => $name,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to create Paddle customer: ' . $response->body());
        }

        return $response->json('data.id');
    }

    /**
     * Create a transaction for a bespoke, consultation-priced deal: two non-catalog
     * price line items (recurring monthly + one-time activation) against the single
     * catalog product, collection_mode automatic so Paddle returns a checkout.
     *
     * $trialDays only affects the recurring monthly item — Paddle has no trial_period
     * concept for one-time items, so the activation fee always charges immediately
     * on checkout regardless of trial.
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

        $monthlyPriceData = [
            'description' => 'Monthly subscription',
            'product_id' => $this->productId,
            'unit_price' => [
                'amount' => (string) round($monthlyPrice * 100),
                'currency_code' => 'USD',
            ],
            'billing_cycle' => ['interval' => 'month', 'frequency' => 1],
        ];

        if ($trialDays) {
            $monthlyPriceData['trial_period'] = ['interval' => 'day', 'frequency' => $trialDays];
        }

        $response = $this->client()->post('/transactions', [
            'customer_id' => $paddleCustomerId,
            'collection_mode' => 'automatic',
            'items' => [
                [
                    'price' => $monthlyPriceData,
                    'quantity' => 1,
                ],
                [
                    'price' => [
                        'description' => 'One-time activation fee',
                        'product_id' => $this->productId,
                        'unit_price' => [
                            'amount' => (string) round($activationPrice * 100),
                            'currency_code' => 'USD',
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'custom_data' => ['reference' => $reference],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to create Paddle transaction: ' . $response->body());
        }

        return [
            'id' => $response->json('data.id'),
            'checkout_url' => $response->json('data.checkout.url'),
        ];
    }

    public function getTransaction(string $transactionId): array
    {
        $response = $this->client()->get("/transactions/{$transactionId}");

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch Paddle transaction: ' . $response->body());
        }

        return $response->json('data');
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
}
