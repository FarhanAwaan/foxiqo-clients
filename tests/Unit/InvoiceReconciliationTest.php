<?php

namespace Tests\Unit;

use App\Models\Invoice;
use Tests\TestCase;

/**
 * Invoice::reconcilesWithPaddle() is the one check behind every "doesn't match Paddle" badge across
 * the admin UI — wrong here means either a false alarm on every normal invoice, or a real mismatch
 * (money missing) going unflagged.
 */
class InvoiceReconciliationTest extends TestCase
{
    protected function invoice(array $overrides = []): Invoice
    {
        return (new Invoice())->forceFill(array_merge([
            'amount' => 497.00,
            'paddle_charged_amount' => null,
            'paddle_tax_amount' => null,
        ], $overrides));
    }

    public function test_an_invoice_that_never_went_through_paddle_always_reconciles(): void
    {
        $this->assertTrue($this->invoice(['paddle_charged_amount' => null])->reconcilesWithPaddle());
    }

    public function test_an_exact_match_reconciles(): void
    {
        $this->assertTrue($this->invoice(['paddle_charged_amount' => 497.00])->reconcilesWithPaddle());
    }

    public function test_a_charge_that_also_included_tax_still_reconciles(): void
    {
        // Paddle charged $538.25 ($497 + $41.25 tax) — matches once the tax is accounted for.
        $this->assertTrue($this->invoice(['paddle_charged_amount' => 538.25, 'paddle_tax_amount' => 41.25])->reconcilesWithPaddle());
    }

    public function test_a_real_mismatch_does_not_reconcile(): void
    {
        $this->assertFalse($this->invoice(['paddle_charged_amount' => 550.00])->reconcilesWithPaddle());
        $this->assertFalse($this->invoice(['paddle_charged_amount' => 538.25, 'paddle_tax_amount' => null])->reconcilesWithPaddle(), 'tax was collected but never recorded');
    }

    public function test_a_sub_cent_rounding_difference_is_not_a_mismatch(): void
    {
        $this->assertTrue($this->invoice(['paddle_charged_amount' => 497.005])->reconcilesWithPaddle());
    }
}
