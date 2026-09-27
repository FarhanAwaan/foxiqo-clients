<?php

namespace Tests\Unit;

use App\Support\PaddleMoney;
use Tests\TestCase;

/**
 * Paddle amounts are minor-unit strings ("8800" = $88.00) nested differently on a transaction
 * (details.totals.grand_total) than on an adjustment (totals.total) — mixing those up is exactly
 * the kind of off-by-a-hundred or wrong-field bug that would corrupt every reconciliation figure.
 */
class PaddleMoneyTest extends TestCase
{
    public function test_cents_converts_the_minor_unit_string(): void
    {
        $this->assertSame(88.00, PaddleMoney::cents('8800'));
        $this->assertSame(0.44, PaddleMoney::cents('44'));
        $this->assertSame(0.0, PaddleMoney::cents('0'));
        $this->assertNull(PaddleMoney::cents(null));
    }

    public function test_transaction_totals_prefers_grand_total_the_amount_actually_charged(): void
    {
        // A real captured sandbox transaction: $88.00 charged, no tax, $4.90 fee.
        $totals = PaddleMoney::fromTransactionTotals([
            'subtotal' => '8800', 'tax' => '0', 'total' => '8800', 'grand_total' => '8800', 'fee' => '490', 'earnings' => '8310',
        ]);

        $this->assertSame(['charged' => 88.00, 'tax' => 0.0, 'fee' => 4.90], $totals);
    }

    public function test_transaction_totals_falls_back_to_total_when_grand_total_is_missing(): void
    {
        $totals = PaddleMoney::fromTransactionTotals(['total' => '53825', 'tax' => '4125', 'fee' => '2691']);

        $this->assertSame(538.25, $totals['charged']);
        $this->assertSame(41.25, $totals['tax']);
    }

    public function test_transaction_totals_is_null_safe_for_a_missing_or_empty_payload(): void
    {
        $this->assertSame(['charged' => null, 'tax' => null, 'fee' => null], PaddleMoney::fromTransactionTotals([]));
    }

    public function test_refunded_amount_reads_the_adjustments_own_total_field_not_grand_total(): void
    {
        // Adjustments have no grand_total key at all — only `total`.
        $this->assertSame(50.00, PaddleMoney::refundedAmount(['subtotal' => '5000', 'tax' => '0', 'total' => '5000', 'fee' => '250']));
        $this->assertSame(0.0, PaddleMoney::refundedAmount([]), 'no totals object at all — never null, callers add it directly');
    }
}
