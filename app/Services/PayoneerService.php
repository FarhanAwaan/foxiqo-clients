<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;

class PayoneerService
{
    protected string $apiKey;
    protected string $partnerId;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = SystemSetting::getValue('payoneer_api_key', '');
        $this->partnerId = SystemSetting::getValue('payoneer_partner_id', '');
        $this->baseUrl = config('services.payoneer.base_url', 'https://api.payoneer.com');
    }

    public function createPaymentRequest(
        float $amount,
        string $reference,
        string $payerEmail,
        string $description
    ): array {
        // Note: Actual Payoneer API implementation will vary based on their documentation
        // This is a placeholder structure
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/v4/programs/{$this->partnerId}/payees/payment-requests", [
                'amount' => $amount,
                'currency' => 'USD',
                'reference' => $reference,
                'description' => $description,
                'payer_email' => $payerEmail,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to create Payoneer payment request: ' . $response->body());
        }

        return $response->json();
    }

    public function getPaymentStatus(string $requestId): array
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/v4/programs/{$this->partnerId}/payees/payment-requests/{$requestId}");

        return $response->json();
    }
}
