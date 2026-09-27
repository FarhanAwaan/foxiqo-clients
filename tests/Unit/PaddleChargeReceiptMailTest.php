<?php

namespace Tests\Unit;

use App\Mail\PaddleChargeReceiptMail;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * When Paddle collects a retainer charge the customer is told their card WAS charged. The generic
 * renewal email (a Due Date, "a payment link will be sent separately") would ask them to pay for
 * something already paid — that wording must never leak into this one.
 */
class PaddleChargeReceiptMailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The mail asks for the support address; seeding the cache keeps this test off the database.
        Cache::put('setting.company_email', 'support@example.test', 3600);
    }

    protected function mail(bool $convertedFromTrial, ?Carbon $next = null): PaddleChargeReceiptMail
    {
        $invoice = (new Invoice())->forceFill([
            'invoice_number' => 'INV-202610-0007',
            'amount' => 497,
            'billing_period_start' => '2026-10-24',
            'billing_period_end' => '2026-11-24',
        ]);

        return new PaddleChargeReceiptMail($invoice, 'Front Desk Assistant', 'Hi Sam', $convertedFromTrial, $next);
    }

    public function test_a_renewal_says_the_card_was_charged_and_asks_for_nothing(): void
    {
        $html = $this->mail(false, Carbon::parse('2026-11-24T20:24:00Z'))->render();

        $this->assertStringContainsString('Hi Sam,', $html);
        $this->assertStringContainsString('the card on file was charged', $html);
        $this->assertStringContainsString('$497.00', $html);
        $this->assertStringContainsString('INV-202610-0007', $html);
        $this->assertStringContainsString('Oct 24', $html);
        $this->assertStringContainsString('Nov 24, 2026', $html);
        $this->assertStringContainsString('November 24, 2026', $html, 'the next charge, in the customer\'s calendar');
        $this->assertStringContainsString('nothing you need to do', $html);

        foreach (['Due Date', 'payment link', 'Pay now', 'Pay Now'] as $ask) {
            $this->assertStringNotContainsString($ask, $html, "a paid-wording email must not contain \"{$ask}\"");
        }
    }

    public function test_the_first_charge_after_a_trial_says_so(): void
    {
        $mail = $this->mail(true);
        $html = $mail->render();

        $this->assertStringContainsString('Your free trial has ended', $html);
        $this->assertStringContainsString('charged', $html);
        $this->assertStringContainsString('first month', $html);
        $this->assertStringContainsString('free trial has ended', $mail->envelope()->subject);
        $this->assertStringNotContainsString('Next charge', $html, 'no next-charge row when the date is not known');
    }

    public function test_the_subject_names_what_was_paid_for_and_replies_go_to_support(): void
    {
        $envelope = $this->mail(false)->envelope();

        $this->assertSame('Payment received — Front Desk Assistant', $envelope->subject);
        $this->assertSame('support@example.test', $envelope->replyTo[0]->address);
    }
}
