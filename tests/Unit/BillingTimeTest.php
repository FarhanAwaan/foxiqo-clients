<?php

namespace Tests\Unit;

use App\Support\BillingTime;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Paddle speaks UTC, the app stores dates in APP_TIMEZONE, and customers/admins read dates in
 * the billing display timezone (America/New_York by default). These pin down that a charge date
 * means the same instant — and the same calendar day for a customer — everywhere it appears.
 */
class BillingTimeTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_paddle_utc_becomes_the_app_timezone_without_moving_the_instant(): void
    {
        $at = BillingTime::fromPaddle('2026-10-08T20:17:34.872931Z');

        $this->assertSame(config('app.timezone'), $at->timezoneName);
        $this->assertSame('2026-10-08T20:17:34Z', $at->copy()->utc()->format('Y-m-d\TH:i:s\Z'));
        $this->assertNull(BillingTime::fromPaddle(null));
    }

    public function test_dates_sent_to_paddle_are_utc_without_fractions(): void
    {
        $this->assertSame(
            '2026-10-08T20:17:34Z',
            BillingTime::toPaddle(Carbon::parse('2026-10-09 01:17:34', 'Asia/Karachi'))
        );
    }

    public function test_customers_see_dates_in_the_billing_timezone_not_the_app_timezone(): void
    {
        // 02:30 UTC on Oct 9 is still the evening of Oct 8 in New York.
        $at = Carbon::parse('2026-10-09T02:30:00Z');

        $this->assertSame('October 8, 2026', BillingTime::date($at));
        $this->assertStringContainsString('Thu, Oct 8, 2026', BillingTime::dateTime($at));
        $this->assertStringContainsString('EDT', BillingTime::dateTime($at));
        $this->assertSame('—', BillingTime::date(null));
    }

    public function test_weekend_is_judged_on_the_customers_calendar(): void
    {
        $this->assertTrue(BillingTime::isWeekend(Carbon::parse('2026-10-10T20:17:00Z')), 'Saturday afternoon EDT');
        $this->assertFalse(BillingTime::isWeekend(Carbon::parse('2026-10-08T20:17:00Z')), 'Thursday');
        // Saturday 02:00 UTC is still Friday 22:00 in New York — not a weekend charge.
        $this->assertFalse(BillingTime::isWeekend(Carbon::parse('2026-10-10T02:00:00Z')));
        // Monday 02:00 UTC is Sunday 22:00 in New York — a weekend charge.
        $this->assertTrue(BillingTime::isWeekend(Carbon::parse('2026-10-12T02:00:00Z')));
    }

    public function test_a_period_date_is_the_day_the_customer_was_charged_not_the_servers_day(): void
    {
        config(['app.timezone' => 'Asia/Karachi', 'billing.display_timezone' => 'America/New_York']);

        // 20:24 UTC on Oct 24 is 4:24 PM in New York — the day the customer is told — but already Oct 25 in Karachi.
        $charged = BillingTime::fromPaddle('2026-10-24T20:24:04Z');
        $this->assertSame('2026-10-25', $charged->copy()->startOfDay()->toDateString(), 'what startOfDay() in the app zone used to store');

        $day = BillingTime::businessDay($charged);

        $this->assertSame('2026-10-24', $day->toDateString());
        $this->assertSame('00:00:00', $day->format('H:i:s'));
        $this->assertSame('Asia/Karachi', $day->timezoneName, 'written to a DATE column as its own wall clock, read back as app time');
        $this->assertSame(BillingTime::date($charged), $day->format('F j, Y'), 'the stored day matches the day every email and page shows');
    }

    public function test_the_business_day_does_not_depend_on_the_zone_an_instant_is_expressed_in(): void
    {
        config(['app.timezone' => 'Asia/Karachi', 'billing.display_timezone' => 'America/New_York']);

        foreach (['UTC', 'Asia/Karachi', 'America/New_York', 'Asia/Tokyo', 'Pacific/Auckland'] as $zone) {
            $this->assertSame(
                '2026-10-24',
                BillingTime::businessDay(Carbon::parse('2026-10-24T20:24:04Z')->setTimezone($zone))->toDateString(),
                "the same instant seen from {$zone}"
            );
        }
    }

    public function test_late_evening_utc_is_still_the_previous_day_in_new_york(): void
    {
        config(['app.timezone' => 'Asia/Karachi', 'billing.display_timezone' => 'America/New_York']);

        // 02:30 UTC Oct 25 is 10:30 PM EDT on Oct 24; a few hours later it is Oct 25 in New York too.
        $this->assertSame('2026-10-24', BillingTime::businessDay(Carbon::parse('2026-10-25T02:30:00Z'))->toDateString());
        $this->assertSame('2026-10-25', BillingTime::businessDay(Carbon::parse('2026-10-25T04:00:00Z'))->toDateString());
    }

    public function test_the_business_day_follows_daylight_saving_time(): void
    {
        config(['app.timezone' => 'Asia/Karachi', 'billing.display_timezone' => 'America/New_York']);

        // Clocks go back on Nov 1 2026: 04:30 UTC on Nov 2 is 11:30 PM EST on Nov 1 (it would be Nov 2 under EDT).
        $this->assertSame('2026-11-01', BillingTime::businessDay(Carbon::parse('2026-11-02T04:30:00Z'))->toDateString());
        $this->assertSame('2026-11-02', BillingTime::businessDay(Carbon::parse('2026-11-02T05:30:00Z'))->toDateString());
        // …and on the last EDT evening, 03:30 UTC Nov 1 is 11:30 PM EDT on Oct 31.
        $this->assertSame('2026-10-31', BillingTime::businessDay(Carbon::parse('2026-11-01T03:30:00Z'))->toDateString());
    }

    public function test_month_end_periods_keep_the_days_paddle_gave(): void
    {
        config(['app.timezone' => 'Asia/Karachi', 'billing.display_timezone' => 'America/New_York']);

        // Paddle bills a Jan 31 anchor on Feb 28, then Mar 31 again; the portal mirrors that, it never adds a month itself.
        $days = array_map(
            fn ($iso) => BillingTime::businessDay(BillingTime::fromPaddle($iso))->toDateString(),
            ['2027-01-31T15:00:00Z', '2027-02-28T15:00:00Z', '2027-03-31T14:00:00Z']
        );

        $this->assertSame(['2027-01-31', '2027-02-28', '2027-03-31'], $days);
    }

    public function test_the_end_of_a_business_day_is_an_instant_kept_in_the_app_timezone(): void
    {
        config(['app.timezone' => 'Asia/Karachi', 'billing.display_timezone' => 'America/New_York']);

        $end = BillingTime::endOfBusinessDay(Carbon::parse('2026-10-24T20:24:04Z'));

        // 11:59:59 PM EDT on Oct 24 == 03:59:59 UTC on Oct 25 — not the end of the KARACHI day.
        $this->assertSame('2026-10-25T03:59:59Z', $end->copy()->utc()->format('Y-m-d\TH:i:s\Z'));
        $this->assertSame('Asia/Karachi', $end->timezoneName);
    }

    public function test_days_until_counts_calendar_days_in_the_billing_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-25T14:00:00Z')); // Sep 25, 10:00 in New York

        $this->assertSame(14, BillingTime::daysUntil(Carbon::parse('2026-10-09T20:17:00Z')));
        $this->assertSame(0, BillingTime::daysUntil(Carbon::parse('2026-09-25T23:00:00Z')), 'later today');
        $this->assertSame(1, BillingTime::daysUntil(Carbon::parse('2026-09-26T05:00:00Z')), 'tomorrow 1am New York');
        $this->assertSame(-1, BillingTime::daysUntil(Carbon::parse('2026-09-24T20:00:00Z')));
        $this->assertNull(BillingTime::daysUntil(null));
    }
}
