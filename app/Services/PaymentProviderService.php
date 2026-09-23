<?php

namespace App\Services;

use App\Models\SystemSetting;

/**
 * Central on/off switch for payment rails (Nsave, Paddle, and later Stripe).
 * Every checkout/payment-link entry point should check isEnabled() at both
 * creation time and render time, so flipping a provider off in Settings
 * immediately blocks it rather than only affecting future links.
 */
class PaymentProviderService
{
    public const PROVIDERS = ['nsave', 'paddle', 'stripe'];

    public static function isEnabled(string $provider): bool
    {
        return (bool) SystemSetting::getValue("{$provider}_enabled", false);
    }

    /**
     * Enabled AND has the minimum credentials to actually function.
     */
    public static function isConfigured(string $provider): bool
    {
        if (!static::isEnabled($provider)) {
            return false;
        }

        return match ($provider) {
            // Nsave is manual bank transfer — the toggle is the only gate,
            // bank details come from config/billing.php (env-backed).
            'nsave' => true,
            'paddle' => (bool) SystemSetting::getValue('paddle_api_key')
                && (bool) SystemSetting::getValue('paddle_webhook_secret')
                && (bool) SystemSetting::getValue('paddle_client_side_token')
                && (bool) SystemSetting::getValue('paddle_product_id'),
            'stripe' => (bool) SystemSetting::getValue('stripe_api_key')
                && (bool) SystemSetting::getValue('stripe_webhook_secret'),
            default => false,
        };
    }
}
