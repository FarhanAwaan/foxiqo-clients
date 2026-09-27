<?php

namespace Tests\Unit;

use App\Exceptions\PaddleApiException;
use App\Services\PaddleService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * What the portal sends Paddle, pinned. Settings are read through the cache, so they're seeded
 * there and no database is touched. The first live bug came from this payload: both lines showed
 * only the product name (no customer-facing price name) and the monthly line was a $0 price.
 */
class PaddleTransactionPayloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Cache::put('setting.paddle_api_key', 'pdl_sdbx_test', 3600);
        Cache::put('setting.paddle_environment', 'sandbox', 3600);
        Cache::put('setting.paddle_product_id', 'pro_test', 3600);

        Http::preventStrayRequests();
        Http::fake([
            'sandbox-api.paddle.com/transactions' => Http::response(['data' => ['id' => 'txn_test', 'checkout' => ['url' => 'https://pay.example']]], 201),
        ]);
    }

    protected function sentItems(): array
    {
        $items = null;

        Http::assertSent(function (Request $request) use (&$items) {
            $items = $request->data()['items'] ?? null;

            return $request->method() === 'POST';
        });

        return $items;
    }

    public function test_trial_deal_sends_a_named_recurring_line_and_a_named_setup_line(): void
    {
        $result = (new PaddleService())->createTransaction('ctm_1', 497.0, 599.0, 'deal-uuid', 14);

        $this->assertSame('txn_test', $result['id']);

        [$monthly, $setup] = $this->sentItems();

        $this->assertSame('Monthly retainer', $monthly['price']['name']);
        $this->assertSame('49700', $monthly['price']['unit_price']['amount']);
        $this->assertSame(['interval' => 'month', 'frequency' => 1], $monthly['price']['billing_cycle']);
        $this->assertSame(['interval' => 'day', 'frequency' => 14], $monthly['price']['trial_period']);

        $this->assertSame('One-time setup & activation fee', $setup['price']['name']);
        $this->assertSame('59900', $setup['price']['unit_price']['amount']);
        $this->assertArrayNotHasKey('billing_cycle', $setup['price']);
        $this->assertArrayNotHasKey('trial_period', $setup['price'], 'a one-time fee can never carry a trial');
    }

    public function test_no_trial_means_no_trial_period(): void
    {
        (new PaddleService())->createTransaction('ctm_1', 497.0, 599.0, 'deal-uuid');

        $this->assertArrayNotHasKey('trial_period', $this->sentItems()[0]['price']);
    }

    public function test_a_setup_only_deal_creates_no_recurring_line_at_all(): void
    {
        (new PaddleService())->createTransaction('ctm_1', 0.0, 999.0, 'deal-uuid');

        $items = $this->sentItems();

        $this->assertCount(1, $items);
        $this->assertSame('One-time setup & activation fee', $items[0]['price']['name']);
        $this->assertArrayNotHasKey('billing_cycle', $items[0]['price'], 'a $0/month subscription would bill nothing forever');
    }

    public function test_a_waived_setup_fee_sends_no_zero_dollar_line(): void
    {
        (new PaddleService())->createTransaction('ctm_1', 497.0, 0.0, 'deal-uuid', 14);

        $items = $this->sentItems();

        $this->assertCount(1, $items);
        $this->assertSame('Monthly retainer', $items[0]['price']['name']);
    }

    public function test_a_transaction_with_nothing_to_charge_is_refused_before_calling_paddle(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        try {
            (new PaddleService())->createTransaction('ctm_1', 0.0, 0.0, 'deal-uuid');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_the_transaction_carries_the_deal_reference_so_subscription_events_can_find_it(): void
    {
        (new PaddleService())->createTransaction('ctm_1', 497.0, 599.0, 'deal-uuid-123', 14);

        Http::assertSent(fn (Request $r) => ($r->data()['custom_data']['reference'] ?? null) === 'deal-uuid-123'
            && $r->data()['collection_mode'] === 'automatic'
            && $r->data()['customer_id'] === 'ctm_1');
    }

    public function test_a_missing_product_id_is_a_clear_configuration_error(): void
    {
        Cache::put('setting.paddle_product_id', '', 3600);

        $this->expectExceptionMessage('Paddle product ID is not configured');

        (new PaddleService())->createTransaction('ctm_1', 497.0, 599.0, 'deal-uuid');
    }

    public function test_paddles_own_error_text_is_surfaced(): void
    {
        Http::fake([
            'sandbox-api.paddle.com/subscriptions/sub_1' => Http::response(['error' => ['code' => 'subscription_next_billed_at_too_soon', 'detail' => 'new next_billed_at needs to be 30m0s from now']], 400),
        ]);

        try {
            (new PaddleService())->setNextBilledAt('sub_1', now()->addMinutes(5));
            $this->fail('Expected a PaddleApiException');
        } catch (PaddleApiException $e) {
            $this->assertSame('subscription_next_billed_at_too_soon', $e->getPaddleCode());
            $this->assertSame(400, $e->getHttpStatus());
            $this->assertStringContainsString('needs to be 30m0s from now', $e->getMessage());
        }
    }

    public function test_moving_a_trial_uses_paddles_required_parameters(): void
    {
        Http::fake(['sandbox-api.paddle.com/subscriptions/sub_1' => Http::response(['data' => ['id' => 'sub_1', 'status' => 'trialing']], 200)]);

        (new PaddleService())->setNextBilledAt('sub_1', \Carbon\Carbon::parse('2026-10-12T04:00:00Z'));

        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH'
            && $r->data() === ['next_billed_at' => '2026-10-12T04:00:00Z', 'proration_billing_mode' => 'do_not_bill']);
    }

    public function test_cancel_maps_to_paddles_effective_from(): void
    {
        Http::fake(['sandbox-api.paddle.com/subscriptions/sub_1/cancel' => Http::response(['data' => ['id' => 'sub_1']], 200)]);

        $paddle = new PaddleService();
        $paddle->cancelSubscription('sub_1', false);
        $paddle->cancelSubscription('sub_1', true);

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/cancel') && $r->data() === ['effective_from' => 'next_billing_period']);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/cancel') && $r->data() === ['effective_from' => 'immediately']);
    }
}
