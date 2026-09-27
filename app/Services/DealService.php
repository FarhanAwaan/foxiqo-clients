<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\User;
use App\Support\DealTerms;
use Illuminate\Support\Str;

class DealService
{
    public const MIN_MONTHLY_PRICE = 497.0;
    public const MIN_ACTIVATION_PRICE = 999.0;

    /** Customer/business fields a retainer deal inherits from the setup-only deal it grows out of. */
    private const CUSTOMER_FIELDS = [
        'customer_name', 'business_name', 'industry', 'city', 'country', 'phone', 'email', 'website',
    ];

    public function __construct(
        protected PaddleService $paddleService,
        protected AuditService $auditService,
        protected EmailService $emailService,
    ) {}

    /**
     * The cases a deal can be (see PROJECT.md → "Deal types"):
     *
     *  - recurring, no trial   setup + retainer charged today, then monthly
     *  - recurring + trial     setup today; the retainer starts after N days (or when an admin
     *                          moves/starts it — e.g. "once the assistant is live")
     *  - recurring, no setup   retainer only (setup waived — needs the price-floor override)
     *  - setup_only            a one-time charge and NO subscription; the monthly price is agreed
     *                          later and becomes a retainer deal (pass parent_deal_id)
     *
     * A recurring deal can never have a $0 monthly price: Paddle would create a $0/month
     * subscription that bills nothing forever. "Price not agreed yet" is what setup_only is for.
     *
     * @param array $data customer/business fields, billing_mode, agreed_monthly_price,
     *   activation_price, is_trial, trial_days, parent_deal_id, email_link
     * @throws \InvalidArgumentException a business-rule violation, phrased for the user
     */
    public function create(User $submittedBy, array $data): Deal
    {
        $parent = !empty($data['parent_deal_id']) ? Deal::findOrFail($data['parent_deal_id']) : null;

        if ($parent) {
            $this->assertCanAddRetainer($parent);
            $data = array_merge($data, $parent->only(self::CUSTOMER_FIELDS));
        }

        $mode = $parent ? Deal::MODE_RECURRING : ($data['billing_mode'] ?? Deal::MODE_RECURRING);

        if (!in_array($mode, [Deal::MODE_RECURRING, Deal::MODE_SETUP_ONLY], true)) {
            throw new \InvalidArgumentException('Unknown deal type.');
        }

        $monthlyPrice = $mode === Deal::MODE_RECURRING ? (float) ($data['agreed_monthly_price'] ?? 0) : 0.0;
        // A retainer deal never charges setup again — that was paid on the deal it grows out of.
        $activationPrice = $parent ? 0.0 : (float) ($data['activation_price'] ?? self::MIN_ACTIVATION_PRICE);

        if ($mode === Deal::MODE_RECURRING && $monthlyPrice <= 0) {
            throw new \InvalidArgumentException(
                'The monthly price must be more than $0 — Paddle would otherwise bill $0 every month, forever. '
                . 'If the monthly price isn\'t agreed yet, create a setup-only deal and add the retainer once it is.'
            );
        }

        if ($mode === Deal::MODE_SETUP_ONLY && $activationPrice <= 0) {
            throw new \InvalidArgumentException('A setup-only deal needs a setup fee of more than $0 — there would be nothing to charge.');
        }

        if (!$submittedBy->can('deals.override-price-floor')) {
            if ($mode === Deal::MODE_RECURRING && $monthlyPrice < self::MIN_MONTHLY_PRICE) {
                throw new \InvalidArgumentException(
                    'Monthly price cannot be below $' . number_format(self::MIN_MONTHLY_PRICE, 0) . '.'
                );
            }
            if (!$parent && $activationPrice < self::MIN_ACTIVATION_PRICE) {
                throw new \InvalidArgumentException(
                    'Activation price cannot be below $' . number_format(self::MIN_ACTIVATION_PRICE, 0) . '.'
                );
            }
        }

        // A trial only applies to a monthly line, and Paddle's trial_period needs whole days.
        $isTrial = $mode === Deal::MODE_RECURRING && !empty($data['is_trial']);
        $trialDays = $isTrial ? max(1, min(365, (int) ($data['trial_days'] ?? 30))) : null;

        // Talk to Paddle BEFORE writing the Deal row — if either call throws, nothing
        // gets persisted, so there's never an orphaned Deal with no transaction to pay.
        $uuid = (string) Str::uuid();

        $paddleCustomerId = $this->paddleService->findOrCreateCustomer($data['email'], $data['customer_name']);

        $transaction = $this->paddleService->createTransaction(
            $paddleCustomerId,
            $monthlyPrice,
            $activationPrice,
            $uuid,
            $trialDays
        );

        $deal = Deal::create([
            'uuid' => $uuid,
            'closer_id' => $submittedBy->id,
            'company_id' => $parent?->company_id,
            'parent_deal_id' => $parent?->id,
            'customer_name' => $data['customer_name'],
            'business_name' => $data['business_name'],
            'industry' => $data['industry'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'website' => $data['website'] ?? null,
            // Strings, not floats: Eloquent's decimal cast raises a BigNumber deprecation for floats.
            'agreed_monthly_price' => number_format($monthlyPrice, 2, '.', ''),
            'activation_price' => number_format($activationPrice, 2, '.', ''),
            'billing_mode' => $mode,
            'is_trial' => $isTrial,
            'trial_days' => $trialDays,
            'status' => 'sent',
            'paddle_transaction_id' => $transaction['id'],
        ]);

        $this->auditService->log('deal_created', $deal);

        $this->announceCreated($deal, $submittedBy, !empty($data['email_link']));

        return $deal;
    }

    /**
     * Email the customer their checkout link (when asked to) and tell the admin a deal exists.
     * Never allowed to fail the deal itself — Paddle already has the transaction and the row is
     * saved; a mail problem is reported and the link can be re-sent from the deal page.
     */
    protected function announceCreated(Deal $deal, User $submittedBy, bool $emailLink): void
    {
        try {
            if ($emailLink) {
                $this->emailService->sendDealCheckoutLink($deal);
            }

            $this->emailService->notifyAdmins(
                type: 'admin_deal_created',
                subject: "New deal: {$deal->business_name} — " . DealTerms::summary($deal),
                headline: 'A new deal was created',
                intro: ($submittedBy->full_name ?: $submittedBy->email) . " created a deal for {$deal->business_name}. "
                    . ($emailLink
                        ? "The checkout link was emailed to {$deal->email}. You'll be told when they pay."
                        : 'No email was sent to the customer — copy the checkout link from the deal page.'),
                facts: array_filter([
                    'Customer' => "{$deal->business_name} ({$deal->customer_name})",
                    'Email' => $deal->email,
                    'Deal' => DealTerms::summary($deal),
                    'Due at checkout' => DealTerms::money($deal->dueToday()),
                    'Type' => $deal->isRetainerDeal() ? 'Monthly retainer for an existing customer' : ($deal->isSetupOnly() ? 'Setup only' : 'Setup + monthly retainer'),
                ]),
                ctaUrl: route('deals.show', $deal),
                ctaLabel: 'Open deal',
                deal: $deal
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * A retainer can be added to a paid setup-only deal, once — while a retainer deal is
     * awaiting payment or already paid, another would just be a duplicate charge.
     *
     * @throws \InvalidArgumentException
     */
    public function assertCanAddRetainer(Deal $parent): void
    {
        if (!$parent->isSetupOnly()) {
            throw new \InvalidArgumentException('A monthly retainer can only be added to a setup-only deal.');
        }

        if ($parent->status !== 'paid') {
            throw new \InvalidArgumentException('The setup fee has to be paid before a monthly retainer can be added.');
        }

        $existing = $parent->childDeals()->whereIn('status', ['sent', 'paid'])->first();

        if ($existing) {
            throw new \InvalidArgumentException(
                $existing->status === 'paid'
                    ? 'This deal already has a paid monthly retainer.'
                    : 'A monthly retainer deal is already waiting for payment. Void it first to create a different one.'
            );
        }
    }

    /**
     * Close a deal that hasn't been paid — wrong price, wrong customer, changed their mind.
     * The checkout link stops working. (Paddle's draft transaction is left as it is: an
     * unpaid draft costs nothing and can't be deleted.)
     *
     * @throws \InvalidArgumentException
     */
    public function void(Deal $deal, ?string $reason = null): void
    {
        if ($deal->status !== 'sent') {
            throw new \InvalidArgumentException('Only a deal that is still waiting for payment can be voided.');
        }

        $deal->update(['status' => 'expired']);

        $this->auditService->log('deal_voided', $deal, ['reason' => $reason]);
    }
}
