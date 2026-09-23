<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\SystemSetting;
use App\Services\PaymentProviderService;
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

        if ($deal->status === 'expired' || !$deal->paddle_transaction_id) {
            return view('billing.provider-unavailable');
        }

        return view('billing.paddle-checkout', [
            'deal' => $deal,
            'paddleClientToken' => SystemSetting::getValue('paddle_client_side_token'),
            'paddleEnvironment' => SystemSetting::getValue('paddle_environment', 'sandbox'),
        ]);
    }
}
