<?php

namespace Tests\Unit;

use App\Models\Deal;
use App\Services\PaddleLifecycleService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Two judgements about Paddle charges that must not drift: which completed transactions on a
 * subscription count as a retainer charge at all, and when a charge date counts as overdue.
 * Both are pure — no database.
 */
class PaddleChargeRulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Frozen in the APP timezone: Carbon parses a stored datetime string in the zone of the frozen "now",
        // so a UTC one would make every date read back from a model five hours off.
        Carbon::setTestNow(Carbon::parse('2026-09-25T12:00:00Z')->setTimezone(config('app.timezone')));
        config(['billing.charge_overdue_grace_hours' => 48]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── Which transactions are retainer charges ─────────────────────────────

    public function test_a_normal_renewal_is_a_charge(): void
    {
        $this->assertTrue(PaddleLifecycleService::isRetainerCharge([
            'id' => 'txn_1', 'origin' => 'subscription_recurring',
            'details' => ['totals' => ['total' => '49700', 'grand_total' => '49700']],
        ]));
    }

    public function test_updating_the_card_is_not_a_charge(): void
    {
        // Paddle creates a zero-value transaction carrying the CURRENT billing period; booking it as a
        // renewal would record a paid invoice for money never taken and reset the period's minutes.
        $this->assertFalse(PaddleLifecycleService::isRetainerCharge([
            'id' => 'txn_2', 'origin' => 'subscription_payment_method_change',
            'billing_period' => ['starts_at' => '2026-09-24T20:24:00Z', 'ends_at' => '2026-10-24T20:24:00Z'],
            'details' => ['totals' => ['total' => '0', 'grand_total' => '0']],
        ]));
    }

    public function test_a_zero_total_is_not_a_charge_whatever_its_origin(): void
    {
        $this->assertFalse(PaddleLifecycleService::isRetainerCharge([
            'id' => 'txn_3', 'origin' => 'api', 'details' => ['totals' => ['grand_total' => '0']],
        ]));
        $this->assertFalse(PaddleLifecycleService::isRetainerCharge([
            'id' => 'txn_4', 'details' => ['totals' => ['total' => '0']],
        ]));
    }

    public function test_a_payload_without_totals_is_treated_as_a_charge_as_it_always_was(): void
    {
        $this->assertTrue(PaddleLifecycleService::isRetainerCharge(['id' => 'txn_5']));
        $this->assertTrue(PaddleLifecycleService::isRetainerCharge(['id' => 'txn_6', 'details' => []]));
    }

    public function test_a_usage_overage_one_time_charge_is_never_a_retainer_charge(): void
    {
        // Real amount, real origin — the ONLY thing that marks it as "not a renewal" is the origin,
        // never the amount (a usage charge is real, non-zero money, unlike a card-update transaction).
        $this->assertFalse(PaddleLifecycleService::isRetainerCharge([
            'id' => 'txn_usage', 'origin' => 'subscription_charge',
            'details' => ['totals' => ['total' => '8800', 'grand_total' => '8800']],
        ]));
    }

    // ── When a charge date is overdue ────────────────────────────────────────

    protected function deal(array $overrides = []): Deal
    {
        return (new Deal())->forceFill(array_merge([
            'status' => 'paid',
            'paddle_subscription_id' => 'sub_test',
            'paddle_status' => 'active',
            'paddle_scheduled_change' => null,
            'next_billed_at' => now()->subHours(72),
        ], $overrides));
    }

    public function test_an_active_subscription_past_its_charge_date_by_more_than_the_grace_period_is_overdue(): void
    {
        $this->assertTrue($this->deal()->isChargeOverdue());
        $this->assertTrue($this->deal(['paddle_status' => 'trialing'])->isChargeOverdue(), 'a trial that never converted');
        $this->assertTrue($this->deal(['paddle_status' => 'past_due'])->isChargeOverdue(), 'a failed payment produces no portal invoice, so this is the only place it can show');
    }

    public function test_the_grace_period_is_respected_and_configurable(): void
    {
        $this->assertFalse($this->deal(['next_billed_at' => now()->subHours(47)])->isChargeOverdue());
        $this->assertTrue($this->deal(['next_billed_at' => now()->subHours(49)])->isChargeOverdue());
        $this->assertFalse($this->deal(['next_billed_at' => now()->addDay()])->isChargeOverdue(), 'not due yet');

        config(['billing.charge_overdue_grace_hours' => 72]);
        $this->assertFalse($this->deal(['next_billed_at' => now()->subHours(49)])->isChargeOverdue());
        $this->assertSame(72, Deal::chargeOverdueGraceHours());
    }

    public function test_states_where_no_charge_is_expected_are_never_overdue(): void
    {
        $this->assertFalse($this->deal(['paddle_status' => 'paused'])->isChargeOverdue());
        $this->assertFalse($this->deal(['paddle_status' => 'canceled'])->isChargeOverdue());
        $this->assertFalse($this->deal(['paddle_status' => null])->isChargeOverdue(), 'never synced — unknown, not overdue');
        $this->assertFalse($this->deal(['paddle_scheduled_change' => 'cancel'])->isChargeOverdue(), 'nothing is charged on a cancel date');
        $this->assertFalse($this->deal(['next_billed_at' => null])->isChargeOverdue());
        $this->assertFalse($this->deal(['paddle_subscription_id' => null])->isChargeOverdue(), 'setup-only deals have no subscription');
        $this->assertFalse($this->deal(['status' => 'sent'])->isChargeOverdue(), 'an unpaid deal has nothing to charge');
    }
}
