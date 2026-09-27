<?php

namespace Tests\Unit;

use App\Mail\PaddleUsageChargeReceiptMail;
use App\Models\Agent;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The usage-overage receipt is a SEPARATE email from the retainer's own PaddleChargeReceiptMail
 * (see PaddleChargeReceiptMailTest) — it must name the minutes and the rate, since that's the one
 * thing that isn't already obvious from "you were charged $X".
 */
class PaddleUsageChargeReceiptMailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::put('setting.company_email', 'support@example.test', 3600);
    }

    protected function mail(): PaddleUsageChargeReceiptMail
    {
        $company = (new Company())->forceFill(['name' => 'Acme Dental']);
        $plan = (new Plan())->forceFill(['per_minute_rate' => 0.44]);
        $agent = (new Agent())->forceFill(['name' => 'Front Desk Assistant']);
        $agent->setRelation('company', $company);

        $subscription = (new Subscription())->forceFill(['id' => 1]);
        $subscription->setRelation('agent', $agent);
        $subscription->setRelation('plan', $plan);
        $subscription->setRelation('company', $company);

        $invoice = (new Invoice())->forceFill([
            'invoice_number' => 'INV-202610-0007',
            'amount' => 88.00,
            'usage_minutes' => 200,
            'billing_period_start' => '2026-09-24',
            'billing_period_end' => '2026-10-24',
        ]);
        $invoice->setRelation('company', $company);

        return new PaddleUsageChargeReceiptMail($invoice, $subscription);
    }

    public function test_the_body_names_the_minutes_the_rate_and_the_amount(): void
    {
        $html = $this->mail()->render();

        $this->assertStringContainsString('Dear Acme Dental,', $html);
        $this->assertStringContainsString('Front Desk Assistant', $html);
        $this->assertStringContainsString('200', $html);
        $this->assertStringContainsString('$0.44', $html);
        $this->assertStringContainsString('$88.00', $html);
        $this->assertStringContainsString('INV-202610-0007', $html);
        $this->assertStringContainsString('September 24, 2026', $html);
        $this->assertStringContainsString('October 24, 2026', $html);
        $this->assertStringContainsString('On top of your regular monthly retainer', $html, 'must not read as replacing the retainer charge');
        $this->assertStringContainsString('nothing you need to do', $html);
    }

    public function test_the_subject_names_the_amount_and_the_assistant(): void
    {
        $envelope = $this->mail()->envelope();

        $this->assertSame('Usage charge — $88.00 for Front Desk Assistant', $envelope->subject);
        $this->assertSame('support@example.test', $envelope->replyTo[0]->address);
    }
}
