<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Str;

class DealService
{
    public const MIN_MONTHLY_PRICE = 497.0;
    public const MIN_ACTIVATION_PRICE = 999.0;

    public function __construct(
        protected PaddleService $paddleService,
        protected AuditService $auditService,
    ) {}

    /**
     * @throws \InvalidArgumentException if below the price floor and $submittedBy can't override it
     */
    public function create(User $submittedBy, array $data): Deal
    {
        $monthlyPrice = (float) $data['agreed_monthly_price'];
        $activationPrice = (float) ($data['activation_price'] ?? self::MIN_ACTIVATION_PRICE);

        if (!$submittedBy->can('deals.override-price-floor')) {
            if ($monthlyPrice < self::MIN_MONTHLY_PRICE) {
                throw new \InvalidArgumentException(
                    'Monthly price cannot be below $' . number_format(self::MIN_MONTHLY_PRICE, 0) . '.'
                );
            }
            if ($activationPrice < self::MIN_ACTIVATION_PRICE) {
                throw new \InvalidArgumentException(
                    'Activation price cannot be below $' . number_format(self::MIN_ACTIVATION_PRICE, 0) . '.'
                );
            }
        }

        // Talk to Paddle BEFORE writing the Deal row — if either call throws, nothing
        // gets persisted, so there's never an orphaned Deal with no transaction to pay.
        $uuid = (string) Str::uuid();

        $isTrial = !empty($data['is_trial']);
        $trialDays = $isTrial ? (int) ($data['trial_days'] ?? 30) : null;

        $paddleCustomerId = $this->paddleService->findOrCreateCustomer($data['email'], $data['customer_name']);

        $transaction = $this->paddleService->createTransaction(
            $paddleCustomerId,
            $monthlyPrice,
            $activationPrice,
            $uuid,
            $trialDays
        );

        $deal = Deal::create([
            'uuid' => $uuid,
            'closer_id' => $submittedBy->id,
            'customer_name' => $data['customer_name'],
            'business_name' => $data['business_name'],
            'industry' => $data['industry'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'website' => $data['website'] ?? null,
            'agreed_monthly_price' => $monthlyPrice,
            'activation_price' => $activationPrice,
            'is_trial' => $isTrial,
            'trial_days' => $trialDays,
            'status' => 'sent',
            'paddle_transaction_id' => $transaction['id'],
        ]);

        $this->auditService->log('deal_created', $deal);

        return $deal;
    }
}
