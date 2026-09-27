<?php

namespace Tests\Unit;

use App\Models\Deal;
use App\Support\DealTerms;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The checkout page, the emails and Paddle must agree on what is charged today. Deal::dueToday()
 * is the portal's side of that promise (Paddle's is the transaction it's sent) — the trial
 * checkout page once added the monthly price to it and showed $1,096 where Paddle took $599.
 */
class DealTermsTest extends TestCase
{
    public static function shapes(): array
    {
        return [
            'standard: setup + first month' => [['agreed_monthly_price' => '497.00', 'activation_price' => '599.00'], 1096.00],
            'trial: setup only today' => [['agreed_monthly_price' => '497.00', 'activation_price' => '599.00', 'is_trial' => true, 'trial_days' => 14], 599.00],
            'trial with setup waived: nothing today' => [['agreed_monthly_price' => '497.00', 'activation_price' => '0.00', 'is_trial' => true, 'trial_days' => 14], 0.00],
            'retainer only, no trial' => [['agreed_monthly_price' => '497.00', 'activation_price' => '0.00'], 497.00],
            'setup-only: just the setup fee' => [['agreed_monthly_price' => '0.00', 'activation_price' => '999.00', 'billing_mode' => 'setup_only'], 999.00],
            'setup-only ignores a stray trial flag' => [['agreed_monthly_price' => '0.00', 'activation_price' => '999.00', 'billing_mode' => 'setup_only', 'is_trial' => true], 999.00],
            'retainer deal with a trial: nothing today' => [['agreed_monthly_price' => '497.00', 'activation_price' => '0.00', 'is_trial' => true, 'trial_days' => 7, 'parent_deal_id' => 3], 0.00],
        ];
    }

    #[DataProvider('shapes')]
    public function test_due_today_is_what_paddle_charges_at_checkout(array $attributes, float $expected): void
    {
        $this->assertSame($expected, (new Deal($attributes))->dueToday());
    }

    public function test_the_order_summary_ends_with_the_same_total(): void
    {
        $deal = new Deal(['agreed_monthly_price' => '497.00', 'activation_price' => '599.00', 'is_trial' => true, 'trial_days' => 14]);

        $rows = DealTerms::rows($deal);
        $last = end($rows);

        $this->assertSame('Due today', $last[0]);
        $this->assertSame('$599.00', $last[1]);
        $this->assertTrue($last[2], 'the total is the emphasised row');
        $this->assertSame('$599.00 setup + $497.00/mo after a 14-day trial', DealTerms::summary($deal));
    }

    public function test_setup_only_never_promises_a_monthly_price(): void
    {
        $deal = new Deal(['agreed_monthly_price' => '0.00', 'activation_price' => '999.00', 'billing_mode' => 'setup_only']);

        $this->assertTrue($deal->isSetupOnly());
        $this->assertFalse($deal->hasRecurring());
        $this->assertSame('$999.00 setup + retainer to be agreed', DealTerms::summary($deal));

        $everything = DealTerms::summary($deal) . ' ' . implode(' ', array_map(fn ($row) => $row[0] . ' ' . $row[1], DealTerms::rows($deal)));
        $this->assertStringNotContainsString('$0.00', $everything);
    }

    public function test_deals_default_to_recurring(): void
    {
        $this->assertFalse((new Deal(['billing_mode' => 'recurring']))->isSetupOnly());
        $this->assertTrue((new Deal(['parent_deal_id' => 5]))->isRetainerDeal());
        $this->assertFalse((new Deal())->isRetainerDeal());
    }
}
