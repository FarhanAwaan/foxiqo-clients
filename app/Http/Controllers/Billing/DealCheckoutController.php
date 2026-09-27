<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\SystemSetting;
use App\Services\PaymentProviderService;
use App\Support\DealTerms;
use Illuminate\View\View;

/**
 * Public, unauthenticated checkout page a closer sends after creating a Deal
 * (see DealController/DealService). Opens Paddle's own checkout inline — the
 * customer never leaves clients.foxiqo.com.
 */
class DealCheckoutController extends Controller
{
    public function show(Deal $deal): View
    {
        if (!PaymentProviderService::isEnabled('paddle')) {
            return view('billing.provider-unavailable');
        }

        if ($deal->status === 'paid') {
            return view('billing.deal-paid', compact('deal'));
        }

        // Also refuses a recurring deal saved with a $0 monthly price (created before that was
        // blocked): paying it would start a Paddle subscription that bills nothing, forever.
        if ($deal->status === 'expired' || !$deal->paddle_transaction_id
            || ($deal->hasRecurring() && (float) $deal->agreed_monthly_price <= 0)) {
            return view('billing.provider-unavailable');
        }

        return view('billing.paddle-checkout', [
            'deal' => $deal,
            // The same words the emails use, so the page can never promise a different total than Paddle charges.
            'rows' => DealTerms::rows($deal),
            'paddleClientToken' => SystemSetting::getValue('paddle_client_side_token'),
            'paddleEnvironment' => SystemSetting::getValue('paddle_environment', 'sandbox'),
        ]);
    }
}
