<?php

namespace App\Services;

use App\Jobs\ResolvePendingRetainerReceipt;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use App\Support\BillingTime;
use App\Support\DealTerms;
use App\Support\PaddleMoney;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Keeps the portal in step with Paddle from the moment a deal is paid until the subscription
 * ends, and gives admins the controls to steer it. Three entry points feed it, all through the
 * same idempotent methods so none can double-record anything:
 *
 *  1. Paddle webhooks (PaddleWebhookController) — real-time.
 *  2. `paddle:reconcile` — polls Paddle every few minutes and catches anything a webhook missed
 *     (destination misconfigured, server down mid-deploy). The safety net that makes the flow
 *     hands-off even when a webhook is lost.
 *  3. Admin actions (extend trial, start billing now, cancel, undo cancel).
 *
 * Paddle owns the billing clock. A Deal mirrors the Paddle subscription (status, trial end,
 * next charge, pending cancellation) because it exists from the first payment — long before any
 * Agent/Subscription does. Every customer/admin notification is driven by DETECTED CHANGES to
 * that mirror, so a change made in the portal and the same change made in Paddle's dashboard
 * both notify exactly once.
 */
class PaddleLifecycleService
{
    /** Paddle rejects a charge date under 30 minutes ahead; the slack covers request latency. */
    public const MIN_CHARGE_LEAD_MINUTES = 35;

    /** How often reconcile looks for unrecorded charges on a billing subscription. */
    public const CHARGE_CHECK_MINUTES = 50;

    public function __construct(
        protected PaddleService $paddle,
        protected InvoiceService $invoiceService,
        protected SubscriptionService $subscriptionService,
        protected EmailService $emailService,
        protected AuditService $auditService,
    ) {}

    /**
     * A company flagged purely for demo purposes is fully excluded from PASSIVE billing
     * processing (a webhook arriving, or the paddle:reconcile sweep) — no invoice/payment
     * recorded, no mirror sync, no email — as if the event never arrived. `reconcile()`'s own
     * query already never fetches a demo deal in the first place; this is what actually protects
     * the webhook path, which reaches these methods directly. Deliberate admin actions (Start
     * billing now, Cancel, etc.) are unaffected — see syncSubscription()'s own $origin check.
     */
    protected function isDemo(?Deal $deal, ?Subscription $subscription = null): bool
    {
        return (bool) ($subscription?->company?->is_demo ?? $deal?->company?->is_demo ?? false);
    }

    // ──────────────────────────────────────────────
    // Payments
    // ──────────────────────────────────────────────

    /**
     * A Paddle transaction completed. Either a deal's own checkout payment (provision the
     * customer) or a charge on a subscription we're tracking (record the retainer invoice).
     * $txn is Paddle's transaction entity — the webhook's `data` or an API response.
     */
    public function processCompletedTransaction(array $txn): bool
    {
        $transactionId = $txn['id'] ?? null;

        if (!$transactionId) {
            throw new \Exception('Missing transaction id in transaction.completed payload');
        }

        // A usage overage charge (chargeUsageInvoice()) — never a deal's checkout (that origin is
        // always 'api'/'web') and never a retainer renewal, so it's checked and routed before either
        // of those, on its own path. isRetainerCharge() also excludes it defensively, but the routing
        // here is what actually gets the money recorded at all — falling through to
        // processSubscriptionCharge() would just no-op it and silently orphan a real charge. This is
        // the ONE entry point both the webhook AND the reconcile sweep call, precisely so that routing
        // decision lives in one place.
        if (($txn['origin'] ?? null) === 'subscription_charge' && ($paddleSubscriptionId = $txn['subscription_id'] ?? null)) {
            return $this->applyUsageCharge($paddleSubscriptionId, $txn);
        }

        $deal = Deal::where('paddle_transaction_id', $transactionId)->first();

        if ($deal) {
            $this->processDealPayment($deal, $txn);

            return true;
        }

        if ($paddleSubscriptionId = $txn['subscription_id'] ?? null) {
            return $this->processSubscriptionCharge($paddleSubscriptionId, $txn);
        }

        return false;
    }

    /**
     * A closer's deal has been paid. Provision what can be automated right now: the Company
     * and the client's first (customer) User, invited through the existing signup-token flow.
     * Attaching a real Agent/Subscription stays a manual admin step once the Retell agent
     * actually exists — see SubscriptionService::createFromDeal().
     */
    protected function processDealPayment(Deal $deal, array $txn): void
    {
        $invited = false;
        $paidNow = false;
        $wasVoided = false;

        DB::transaction(function () use ($deal, $txn, &$invited, &$paidNow, &$wasVoided) {
            // Locked so a webhook and the reconcile poll racing on the same payment can't both provision.
            $locked = Deal::whereKey($deal->id)->lockForUpdate()->first();

            if ($locked->status === 'paid') {
                return; // Already processed — a webhook retry, or the poll got there first
            }

            $wasVoided = $locked->status === 'expired';
            $paddleCustomerId = $txn['customer_id'] ?? null;

            $company = $locked->company ?: Company::firstOrCreate(
                ['email' => $locked->email],
                [
                    'name' => $locked->business_name,
                    'phone' => $locked->phone,
                    'city' => $locked->city,
                    'country' => $locked->country,
                    'status' => 'active',
                    'paddle_customer_id' => $paddleCustomerId,
                ]
            );

            // Demo company — checked only after resolving $company (never before): a repeat deal
            // from a known demo customer is usually matched by EMAIL here, not by $locked->company_id
            // (that link is only set below), so checking the relation directly, before this lookup,
            // would silently miss exactly the "another assistant, same demo customer" case. Safe to
            // check after firstOrCreate() either way — a matching company is only ever found here,
            // never created, so bailing out now still leaves nothing new provisioned or recorded.
            if ($company->is_demo) {
                return;
            }

            if ($paddleCustomerId && !$company->paddle_customer_id) {
                $company->update(['paddle_customer_id' => $paddleCustomerId]);
            }

            $user = User::where('email', $locked->email)->first();

            if (!$user) {
                [$firstName, $lastName] = $this->splitName($locked->customer_name);

                $user = User::create([
                    'company_id' => $company->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $locked->email,
                    'phone' => $locked->phone,
                    'role' => 'customer',
                    'status' => 'pending',
                ]);

                $this->emailService->sendUserInvitation($user, $user->generateSignupToken());
                $invited = true;
            }

            $locked->update([
                'status' => 'paid',
                'company_id' => $company->id,
                // Keep an id an earlier subscription.created event already linked.
                'paddle_subscription_id' => $txn['subscription_id'] ?? $locked->paddle_subscription_id,
            ]);

            $this->auditService->log('deal_paid', $locked);

            // A deal's checkout can charge one transaction for TWO things at once (setup fee + first
            // month) — Paddle gives back one combined total for it, not a total per line, so what each
            // invoice's OWN reconciliation figures show is that total split in proportion to what each
            // invoice is FOR, e.g. $599 setup + $497 first month → 55%/45% of whatever Paddle actually
            // charged (tax included). The two portions always add back up to the transaction's real
            // total, so summing every invoice's paddle_charged_amount never double-counts one transaction.
            $activationAmount = (float) $locked->activation_price > 0 ? (float) $locked->activation_price : 0.0;
            $retainerAmount = $locked->hasRecurring() && !$locked->is_trial && (float) $locked->agreed_monthly_price > 0
                ? (float) $locked->agreed_monthly_price : 0.0;
            $combinedAgreed = $activationAmount + $retainerAmount;
            $txnTotals = PaddleMoney::fromTransactionTotals($txn['details']['totals'] ?? []);
            $portionOf = function (float $amount) use ($combinedAgreed, $txnTotals) {
                $share = $combinedAgreed > 0 ? $amount / $combinedAgreed : 0;

                return array_map(fn ($v) => $v === null ? null : round($v * $share, 2), $txnTotals);
            };

            // The activation fee is real money Paddle just collected but — unlike the monthly
            // retainer — has no Subscription to attach an invoice to later, so it's recorded
            // here or not at all. Skipped when there is no setup fee (waived, or a retainer deal).
            if ($activationAmount > 0) {
                $activationInvoice = $this->invoiceService->createStandaloneInvoice(
                    $company->id,
                    'activation',
                    $activationAmount,
                    $txn['id'],
                    $txn['status'] ?? null,
                    $locked->id,
                    paddleTotals: $portionOf($activationAmount)
                );

                // No PaymentReceived event: the customer already gets Paddle's receipt (with the PDF
                // invoice) plus the deal-paid email below — a "Payment Confirmed" on top is a duplicate.
                $this->invoiceService->markAsPaid($activationInvoice, 'paddle', $txn['id'], fireEvent: false);
            }

            // No trial: the first month was charged inside this same checkout, so it is recorded now
            // like every other Paddle charge — against the deal until an assistant exists, and adopted by
            // its Subscription then (SubscriptionService::createFromDeal). Paddle's own billing period
            // says which month it covers, which a later "today + 30 days" guess would get wrong.
            if ($retainerAmount > 0) {
                $periodStart = BillingTime::fromPaddle($txn['billing_period']['starts_at'] ?? null) ?? now();
                $periodEnd = BillingTime::fromPaddle($txn['billing_period']['ends_at'] ?? null) ?? $periodStart->copy()->addMonthNoOverflow();

                $retainerInvoice = $this->invoiceService->createStandaloneInvoice(
                    $company->id,
                    'subscription',
                    $retainerAmount,
                    $txn['id'],
                    $txn['status'] ?? null,
                    $locked->id,
                    BillingTime::businessDay($periodStart),
                    BillingTime::businessDay($periodEnd),
                    $portionOf($retainerAmount)
                );

                $this->invoiceService->markAsPaid($retainerInvoice, 'paddle', $txn['id'], fireEvent: false);
            }

            $paidNow = true;
        });

        if (!$paidNow) {
            return;
        }

        $deal->refresh();

        // Pull Paddle's real trial/billing dates BEFORE telling anyone about them, so the date in
        // the email is the date Paddle will charge. A failure here isn't fatal — reconcile retries.
        if ($deal->hasRecurring() && $deal->paddle_subscription_id) {
            $this->refreshQuietly($deal);
            $deal->refresh();
        }

        $this->emailService->sendDealPaid($deal, $invited);
        $this->alertDealPaid($deal, $wasVoided);
    }

    /**
     * Whether a completed transaction on a subscription is a retainer CHARGE (a renewal, or a
     * trial's first charge) — as opposed to something else that also completes as a transaction on
     * the same subscription:
     *  - `subscription_payment_method_change`: a zero-value transaction updating the card on file,
     *    carrying the CURRENT billing period; recorded as a renewal it would book a paid invoice for
     *    money that was never charged.
     *  - `subscription_charge`: a usage overage one-time charge (chargeUsageInvoice()) — its own
     *    transaction, routed to applyUsageCharge() before this is ever consulted. Excluded here too,
     *    defensively, so a change to that routing can't silently misfile one as a renewal instead of
     *    just failing to record it.
     * A payload without totals is treated as a charge, as it always was.
     */
    public static function isRetainerCharge(array $txn): bool
    {
        if (in_array($txn['origin'] ?? null, ['subscription_payment_method_change', 'subscription_charge'], true)) {
            return false;
        }

        $total = $txn['details']['totals']['grand_total'] ?? $txn['details']['totals']['total'] ?? null;

        return $total === null || (int) $total > 0;
    }

    /**
     * Paddle charged a subscription we track — the first real charge after a trial, or any
     * later month. Idempotent by transaction id. Returns whether it recorded anything new.
     */
    protected function processSubscriptionCharge(string $paddleSubscriptionId, array $txn): bool
    {
        if (!self::isRetainerCharge($txn)) {
            return false;
        }

        $deal = Deal::where('paddle_subscription_id', $paddleSubscriptionId)->first();
        $subscription = Subscription::where('paddle_subscription_id', $paddleSubscriptionId)->first();

        if (!$deal && !$subscription) {
            return false; // Not one of ours
        }

        $deal ??= $subscription?->deal;

        if ($this->isDemo($deal, $subscription)) {
            return false; // Demo company — see isDemo()
        }

        $transactionId = $txn['id'];
        $isFirstCharge = false;
        $advanced = true;
        $invoice = null;
        $usageInvoice = null;

        $recorded = DB::transaction(function () use ($deal, $subscription, $txn, $transactionId, &$isFirstCharge, &$advanced, &$invoice, &$usageInvoice) {
            // Serialise concurrent handling of the same charge (webhook + reconcile poll): whoever
            // takes the row lock second re-checks below and finds the invoice already there. The
            // subscription is used as READ under the lock — the copy fetched before the transaction may
            // predate a charge the other worker just recorded, which would fool the "is this period
            // newer?" check into rolling the period back.
            if ($subscription) {
                $subscription = Subscription::whereKey($subscription->id)->lockForUpdate()->first();
            } else {
                Deal::whereKey($deal->id)->lockForUpdate()->first();
            }

            if (Invoice::where('paddle_transaction_id', $transactionId)->where('invoice_type', 'subscription')->exists()) {
                return false; // Already recorded this exact charge
            }

            if (!$subscription && !$deal?->company_id) {
                Log::warning("Paddle charge {$transactionId} matched deal {$deal?->uuid}, which has no customer yet — not recorded.");

                return false;
            }

            $isFirstCharge = !Invoice::where('invoice_type', 'subscription')
                ->whereNotNull('paddle_transaction_id')
                ->where(function ($q) use ($deal, $subscription) {
                    if ($subscription) {
                        $q->orWhere('subscription_id', $subscription->id);
                    }
                    if ($deal) {
                        $q->orWhere('deal_id', $deal->id);
                    }
                })
                ->exists();

            // Paddle's own billing period is the source of truth for what this charge covers.
            $periodStart = BillingTime::fromPaddle($txn['billing_period']['starts_at'] ?? null) ?? now();
            $periodEnd = BillingTime::fromPaddle($txn['billing_period']['ends_at'] ?? null) ?? $periodStart->copy()->addMonthNoOverflow();
            $status = $txn['status'] ?? null;
            $totals = PaddleMoney::fromTransactionTotals($txn['details']['totals'] ?? []);

            if ($subscription) {
                $wasTrial = (bool) $subscription->is_trial;

                // Asked before recording, which moves the period it is asked about.
                $advanced = $this->subscriptionService->paddlePeriodAdvances($subscription, $periodStart);

                $invoice = $this->subscriptionService->recordPaddleCharge($subscription, $transactionId, $status, $periodStart, $periodEnd, $totals);

                // recordPaddleCharge() just created the closed period's usage invoice (if there was
                // any usage to bill) as a bare record — no payment link, since this subscription is
                // Paddle-managed and chargeUsageInvoice() below decides how it actually gets collected.
                if ($advanced) {
                    $usageInvoice = Invoice::where('subscription_id', $subscription->id)
                        ->where('invoice_type', 'usage')
                        ->whereNull('paddle_transaction_id')
                        ->where('status', 'draft')
                        ->latest('id')
                        ->first();
                }

                $this->auditService->log($wasTrial ? 'paddle_trial_converted' : 'paddle_renewal_reconciled', $subscription, array_filter([
                    'invoice_id' => $invoice->id,
                    'paddle_transaction_id' => $transactionId,
                    'reason' => $advanced ? null : 'Charge for an earlier period than the current one: recorded as a paid invoice, the subscription was not moved back.',
                ]));

                return true;
            }

            // No assistant is attached to this deal yet, but Paddle has collected real money
            // (typically a trial that ended before the assistant was built). Record it against
            // the customer so it isn't invisible; the Subscription adopts it once created.
            $invoice = $this->invoiceService->createStandaloneInvoice(
                $deal->company_id,
                'subscription',
                (float) $deal->agreed_monthly_price,
                $transactionId,
                $status,
                $deal->id,
                BillingTime::businessDay($periodStart),
                BillingTime::businessDay($periodEnd),
                $totals
            );

            $this->invoiceService->markAsPaid($invoice, 'paddle', $transactionId, fireEvent: false);

            $this->auditService->log('paddle_charge_recorded', $deal, [
                'invoice_id' => $invoice->id,
                'paddle_transaction_id' => $transactionId,
            ]);

            return true;
        });

        if (!$recorded) {
            return false;
        }

        if ($deal) {
            $this->refreshQuietly($deal);
            $deal = $deal->fresh();
            $this->alertChargeCollected($deal, $isFirstCharge, $subscription === null);
        }

        // Outside the transaction (it calls Paddle) — money for this period is already recorded and
        // safe either way; a failure here just falls back to the normal manual payment link.
        $usageChargeInFlight = $usageInvoice && $this->chargeUsageInvoice($usageInvoice);

        // Only a charge that carried the subscription into a new period is news to the customer; an email
        // about last month's payment arriving today would just confuse. Never allowed to fail a charge
        // that is already recorded.
        if ($advanced && $invoice) {
            if ($usageChargeInFlight) {
                // Hold this receipt rather than send it now: the usage charge just sent to Paddle above
                // (chargeUsageInvoice()) usually settles within a few seconds, and the customer should get
                // ONE "$497 + $88 = $585 charged" email for the two charges that happened the same day,
                // not two separate ones. Whichever resolves this first wins the row lock in
                // resolveRetainerReceipt(): applyUsageCharge() / applyUsageChargeFailure() if the usage
                // charge settles in time, or this timeout job if it doesn't.
                $invoice->update(['usage_receipt_pending_invoice_id' => $usageInvoice->id]);
                ResolvePendingRetainerReceipt::dispatch($invoice)
                    ->delay(now()->addMinutes((int) config('billing.combined_receipt_wait_minutes', 2)));
            } else {
                try {
                    $this->emailService->sendPaddleChargeReceipt($invoice, $subscription?->fresh(), $deal, $isFirstCharge);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return true;
    }

    /**
     * Paddle collected a usage-overage one-time charge (chargeUsageInvoice()) — origin
     * subscription_charge, routed here by processCompletedTransaction() before either the deal-
     * checkout or retainer-renewal paths ever see it. Idempotent by transaction id, same as every
     * other charge. Returns whether it recorded anything new.
     */
    protected function applyUsageCharge(string $paddleSubscriptionId, array $txn): bool
    {
        $subscription = Subscription::where('paddle_subscription_id', $paddleSubscriptionId)->first();
        $transactionId = $txn['id'];

        if (!$subscription) {
            Log::warning("Paddle usage charge {$transactionId} matched no tracked subscription ({$paddleSubscriptionId}).");

            return false;
        }

        if ($this->isDemo(null, $subscription)) {
            return false; // Demo company — see isDemo()
        }

        $usageInvoice = null;
        $wasUnmatched = false;

        $recorded = DB::transaction(function () use ($subscription, $txn, $transactionId, &$usageInvoice, &$wasUnmatched) {
            // Locked for the same reason as processSubscriptionCharge(): a webhook and the reconcile
            // sweep can both see this transaction.
            $subscription = Subscription::whereKey($subscription->id)->lockForUpdate()->first();

            if (Invoice::where('paddle_transaction_id', $transactionId)->exists()) {
                return false; // Already recorded — a webhook retry, or the sweep got there first
            }

            $totals = PaddleMoney::fromTransactionTotals($txn['details']['totals'] ?? []);
            $status = $txn['status'] ?? null;

            // chargeUsageInvoice() never lets more than one usage invoice be awaiting a charge on the
            // same subscription at once (see its own comment), so this is unambiguous: whichever one
            // is open is the one this transaction is for.
            $usageInvoice = Invoice::where('subscription_id', $subscription->id)
                ->where('invoice_type', 'usage')
                ->whereIn('status', ['draft', 'sent'])
                ->whereNull('paddle_transaction_id')
                ->oldest('id')
                ->first();

            if (!$usageInvoice) {
                // Nothing here was expecting a charge — e.g. a one-time charge added by hand in
                // Paddle's own dashboard. Recorded anyway (no penny should go untracked just because
                // the portal didn't initiate it); usage_minutes is left null since there's no invoice
                // this maps back to explaining what it was for.
                $wasUnmatched = true;

                $usageInvoice = Invoice::create([
                    'invoice_number' => app(InvoiceService::class)->generateInvoiceNumber(),
                    'subscription_id' => $subscription->id,
                    'company_id' => $subscription->company_id,
                    'invoice_type' => 'usage',
                    'amount' => $totals['charged'] ?? 0,
                    'status' => 'draft',
                    'billing_period_start' => $subscription->current_period_start,
                    'billing_period_end' => $subscription->current_period_end,
                    'due_date' => BillingTime::businessDay(now()),
                    'notes' => 'Recorded from a Paddle one-time charge the portal did not request — not tied to a specific usage invoice.',
                ]);
            }

            $usageInvoice->update([
                'paddle_transaction_id' => $transactionId,
                'paddle_status' => $status,
            ]);
            $this->invoiceService->recordPaddleTotals($usageInvoice, $totals);
            $this->invoiceService->markAsPaid($usageInvoice, 'paddle', $transactionId, fireEvent: false);

            $this->auditService->log($wasUnmatched ? 'usage_charge_unmatched' : 'usage_charge_collected', $usageInvoice, [
                'paddle_transaction_id' => $transactionId,
            ]);

            return true;
        });

        if (!$recorded) {
            return false;
        }

        if ($wasUnmatched) {
            return true;
        }

        // If a retainer invoice is still waiting to hear how this exact usage charge turned out
        // (processSubscriptionCharge() set this up right before requesting it), that decides the
        // receipt — one combined email — instead of this usage invoice sending its own separate one.
        $pendingRetainer = Invoice::where('usage_receipt_pending_invoice_id', $usageInvoice->id)->first();

        if ($pendingRetainer) {
            $this->resolveRetainerReceipt($pendingRetainer->id);

            return true;
        }

        try {
            $this->emailService->sendPaddleUsageChargeReceipt($usageInvoice->fresh(), $subscription->fresh());
        } catch (\Throwable $e) {
            report($e);
        }

        return true;
    }

    /**
     * Charge the card Paddle already has on file for this Paddle-managed subscription's usage
     * invoice, right now — called once, straight after recordPaddleCharge() creates the record for
     * the period that just closed (see processSubscriptionCharge()). What happens to the money next
     * (settled paid, or the card declines) comes back later as its own transaction.completed /
     * .payment_failed, applied by applyUsageCharge() / processPaymentFailed().
     *
     * Never lets a SECOND usage invoice go to Paddle while an older one for the same subscription is
     * still unresolved — applyUsageCharge() has no way to tell two open ones apart, so the older one
     * must clear (paid, or its charge attempt failed and fell back to the manual link) first.
     *
     * @return bool whether a charge request was actually sent to Paddle and is now awaiting an async
     *   outcome (true), as opposed to something resolved right here with nothing left pending (false:
     *   deferred behind an older one, below Paddle's minimum, or Paddle refused the request itself).
     *   The caller uses this to decide whether the retainer's own receipt should wait to combine with
     *   this one — see processSubscriptionCharge().
     */
    public function chargeUsageInvoice(Invoice $usageInvoice): bool
    {
        $subscription = $usageInvoice->subscription;
        // billing_period_start/end are DATE columns already holding the correct business day
        // (BillingTime::businessDay() at write time) — format directly rather than through
        // BillingTime::date(), which would re-convert an already-correct calendar day to the
        // display timezone and could shift it across midnight onto the wrong day.
        $period = $usageInvoice->billing_period_start->format('F j, Y') . ' – ' . $usageInvoice->billing_period_end->format('F j, Y');

        $olderOpen = Invoice::where('subscription_id', $subscription->id)
            ->where('invoice_type', 'usage')
            ->where('id', '!=', $usageInvoice->id)
            ->whereNull('paddle_transaction_id')
            ->whereIn('status', ['draft', 'sent'])
            ->exists();

        if ($olderOpen) {
            $usageInvoice->update(['notes' => 'An earlier usage charge for this subscription has not been resolved yet — left for manual collection until that one clears.']);
            $this->auditService->log('usage_charge_deferred', $usageInvoice);
            $this->invoiceService->createPaymentLink($usageInvoice);

            return false;
        }

        $cents = (int) round((float) $usageInvoice->amount * 100);
        $minimumCents = (int) config('billing.paddle_minimum_charge_cents', 100);

        if ($cents < $minimumCents) {
            $usageInvoice->update(['notes' => 'Too small to charge automatically (Paddle requires at least ' . DealTerms::money($minimumCents / 100) . ') — left for manual collection.']);
            $this->auditService->log('usage_charge_below_minimum', $usageInvoice);
            $this->invoiceService->createPaymentLink($usageInvoice);

            return false;
        }

        try {
            $this->paddle->createOneTimeCharge(
                $subscription->paddle_subscription_id,
                (float) $usageInvoice->amount,
                "Usage — {$usageInvoice->usage_minutes} min",
                "Usage — {$usageInvoice->usage_minutes} minute(s), {$period}"
            );

            // Not yet paid — Paddle processes this asynchronously, same as every charge in this app.
            // 'sent' (not 'draft') so it reads as "awaiting payment" rather than untouched if anyone
            // looks at it before the webhook lands.
            $usageInvoice->update(['status' => 'sent', 'sent_at' => now()]);
            $this->auditService->log('usage_charge_requested', $usageInvoice);

            return true;
        } catch (\Throwable $e) {
            $this->auditService->log('usage_charge_request_failed', $usageInvoice, ['error' => $e->getMessage()]);

            $deal = $subscription->deal;

            $this->emailService->notifyAdmins(
                type: 'admin_usage_charge_failed',
                subject: "Couldn't charge usage automatically: {$subscription->company->name}",
                headline: 'A usage charge could not be sent to Paddle',
                intro: 'Paddle refused the automatic charge of ' . DealTerms::money($usageInvoice->amount) . " for {$usageInvoice->usage_minutes} minutes of usage ({$period}). Paddle said: "
                    . $e->getMessage() . ' A payment link has been created as a fallback, and this will need to be collected some other way (unlike a declined RENEWAL, Paddle does not automatically retry a one-time charge).',
                facts: array_filter([
                    'Customer' => $subscription->company->name,
                    'Amount' => DealTerms::money($usageInvoice->amount),
                    'Invoice' => $usageInvoice->invoice_number,
                ]),
                ctaUrl: route('admin.invoices.show', $usageInvoice),
                ctaLabel: 'Open invoice',
                tone: 'danger',
                deal: $deal
            );

            $this->invoiceService->createPaymentLink($usageInvoice);

            return false;
        }
    }

    /**
     * The one-time charge chargeUsageInvoice() sent to Paddle came back declined (called from
     * processPaymentFailed()). Unlike a retainer going past_due, Paddle does not retry a one-time
     * charge on its own — this alert says so, and a payment link is created as the fallback so the
     * amount is still collectible.
     */
    protected function applyUsageChargeFailure(string $paddleSubscriptionId, string $transactionId, ?string $reason): void
    {
        $subscription = Subscription::where('paddle_subscription_id', $paddleSubscriptionId)->first();

        if (!$subscription) {
            return;
        }

        if ($this->isDemo(null, $subscription)) {
            return; // Demo company — see isDemo()
        }

        $usageInvoice = Invoice::where('subscription_id', $subscription->id)
            ->where('invoice_type', 'usage')
            ->whereIn('status', ['draft', 'sent'])
            ->whereNull('paddle_transaction_id')
            ->oldest('id')
            ->first();

        if (!$usageInvoice) {
            // Nothing was waiting on this one (already settled some other way, or an unrequested
            // Paddle-side charge) — still worth a log line, nothing more to act on.
            Log::warning("Paddle usage charge {$transactionId} failed but no open usage invoice was found on subscription {$paddleSubscriptionId}.");

            return;
        }

        $usageInvoice->update([
            'paddle_status' => 'payment_failed',
            'notes' => 'The automatic Paddle charge for this usage was declined — see Activity. A payment link has been created as a fallback.',
        ]);

        $this->auditService->log('usage_charge_failed', $usageInvoice, [
            'paddle_transaction_id' => $transactionId,
            'error' => $reason,
        ]);

        // billing_period_start/end are DATE columns already holding the correct business day
        // (BillingTime::businessDay() at write time) — format directly rather than through
        // BillingTime::date(), which would re-convert an already-correct calendar day to the
        // display timezone and could shift it across midnight onto the wrong day.
        $period = $usageInvoice->billing_period_start->format('F j, Y') . ' – ' . $usageInvoice->billing_period_end->format('F j, Y');
        $deal = $subscription->deal;

        $this->emailService->notifyAdmins(
            type: 'admin_usage_charge_failed',
            subject: "Usage charge declined: {$subscription->company->name}",
            headline: 'A usage charge was declined',
            intro: "Paddle tried to charge {$subscription->company->name}'s card for " . DealTerms::money($usageInvoice->amount) . " in usage ({$period}), but it was declined"
                . ($reason ? ' (' . str_replace('_', ' ', $reason) . ')' : '') . '. Paddle will NOT retry this automatically the way it retries a subscription renewal — a payment link has been created as a fallback, so you may want to reach out or ask them to update their card.',
            facts: array_filter([
                'Customer' => $subscription->company->name,
                'Amount' => DealTerms::money($usageInvoice->amount),
                'Invoice' => $usageInvoice->invoice_number,
                'Reason' => $reason ? str_replace('_', ' ', $reason) : null,
            ]),
            ctaUrl: route('admin.invoices.show', $usageInvoice),
            ctaLabel: 'Open invoice',
            tone: 'danger',
            deal: $deal
        );

        $this->invoiceService->createPaymentLink($usageInvoice);

        // A retainer invoice waiting to combine with this one now has its answer: no, send its own
        // receipt alone — a declined usage charge is never folded into a customer-facing receipt.
        if ($pendingRetainer = Invoice::where('usage_receipt_pending_invoice_id', $usageInvoice->id)->first()) {
            $this->resolveRetainerReceipt($pendingRetainer->id);
        }
    }

    /**
     * Send the retainer's own charge receipt — combined with its usage-overage sibling if that
     * settled paid by now, or alone if it hasn't (declined, still pending, or there never was one to
     * begin with). Three places call this for the SAME invoice and race for its row lock:
     * processSubscriptionCharge() never calls it directly (only sets the marker and either sends
     * immediately or schedules ResolvePendingRetainerReceipt), applyUsageCharge() calls it the moment
     * the usage charge settles paid, and applyUsageChargeFailure() calls it the moment it's declined —
     * whichever gets here first (typically one of those two, within seconds) wins; the delayed job is
     * purely the timeout fallback for a usage charge that never resolves at all.
     *
     * Idempotent: a no-op once `usage_receipt_pending_invoice_id` is cleared, however that happened,
     * so it's safe for more than one of the three to call it without coordinating beyond the lock.
     */
    public function resolveRetainerReceipt(int $retainerInvoiceId): void
    {
        $invoice = null;
        $usageInvoice = null;
        $shouldSend = false;

        DB::transaction(function () use ($retainerInvoiceId, &$invoice, &$usageInvoice, &$shouldSend) {
            $invoice = Invoice::whereKey($retainerInvoiceId)->lockForUpdate()->first();

            if (!$invoice || !$invoice->usage_receipt_pending_invoice_id) {
                return; // Already resolved by whichever of the three got here first
            }

            $usageInvoice = Invoice::find($invoice->usage_receipt_pending_invoice_id);
            $invoice->update(['usage_receipt_pending_invoice_id' => null]);
            $shouldSend = true;

            // Combine it in only if it actually settled paid — still pending (the timeout fired first)
            // or declined both mean the retainer's receipt goes out alone, exactly as if there had never
            // been a usage charge to wait for.
            if (!$usageInvoice || $usageInvoice->status !== 'paid') {
                $usageInvoice = null;
            }
        });

        if (!$shouldSend) {
            return;
        }

        $subscription = $invoice->subscription()->with('deal')->first();
        $deal = $subscription?->deal;

        try {
            // convertedFromTrial is always false here: a trial's first period never bills usage (trial
            // minutes are free), so a usage sibling — pending, paid, or failed — never coincides with a
            // trial-conversion charge; see createUsageInvoiceForClosedPeriod()'s own !$wasTrial guard.
            $this->emailService->sendPaddleChargeReceipt($invoice, $subscription, $deal, convertedFromTrial: false, usageInvoice: $usageInvoice);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * A payment attempt failed. A customer's failed checkout attempt (typo'd card, insufficient
     * funds) does NOT expire the deal — Paddle lets them retry on the same link, and locking
     * them out over one mistyped digit would just create work. The admin is told instead.
     */
    public function processPaymentFailed(array $txn): void
    {
        $transactionId = $txn['id'] ?? null;

        if (!$transactionId) {
            return;
        }

        $payments = $txn['payments'] ?? [];
        $reason = is_array($payments) && $payments ? ($payments[array_key_last($payments)]['error_code'] ?? null) : null;

        $deal = Deal::where('paddle_transaction_id', $transactionId)->first();

        if ($deal && $deal->status !== 'paid' && $this->isDemo($deal)) {
            return; // Demo company — see isDemo()
        }

        if ($deal && $deal->status !== 'paid') {
            $this->auditService->log('deal_payment_failed', $deal, [
                'paddle_transaction_id' => $transactionId,
                'error' => $reason,
            ]);

            $this->emailService->notifyAdmins(
                type: 'admin_payment_failed',
                subject: "Payment attempt failed: {$deal->business_name}",
                headline: 'A checkout payment attempt failed',
                intro: "{$deal->customer_name} tried to pay for {$deal->business_name} but the payment didn't go through. The checkout link is still valid and they can try again, so you may want to reach out.",
                facts: array_filter([
                    'Customer' => "{$deal->business_name} ({$deal->customer_name})",
                    'Email' => $deal->email,
                    'Amount due' => DealTerms::money($deal->dueToday()),
                    'Reason' => $reason ? str_replace('_', ' ', $reason) : null,
                ]),
                ctaUrl: route('deals.show', $deal),
                ctaLabel: 'Open deal',
                tone: 'warning',
                deal: $deal
            );

            return;
        }

        // A usage overage charge failed (chargeUsageInvoice()) — settle THAT invoice, not the
        // retainer one below: unlike a renewal, Paddle does not automatically retry a one-time charge,
        // so this needs its own alert saying so, and its own fallback to collect the money another way.
        if (($txn['origin'] ?? null) === 'subscription_charge' && ($paddleSubscriptionId = $txn['subscription_id'] ?? null)) {
            $this->applyUsageChargeFailure($paddleSubscriptionId, $transactionId, $reason);

            return;
        }

        // Not a deal's first payment — a later charge failed. Keep Paddle's status visible on
        // the invoice and in the timeline; the "past due" alert comes from the subscription event.
        $paddleSubscriptionId = $txn['subscription_id'] ?? null;
        $subscription = $paddleSubscriptionId ? Subscription::where('paddle_subscription_id', $paddleSubscriptionId)->first() : null;
        $deal ??= $subscription?->deal ?? ($paddleSubscriptionId ? Deal::where('paddle_subscription_id', $paddleSubscriptionId)->first() : null);

        if (!$subscription && !$deal) {
            return;
        }

        if ($this->isDemo($deal, $subscription)) {
            return; // Demo company — see isDemo()
        }

        $invoice = $subscription?->invoices()
            ->where('invoice_type', 'subscription')
            ->where('status', '!=', 'paid')
            ->oldest('billing_period_start')
            ->first();

        $invoice?->update(['paddle_status' => $txn['status'] ?? 'payment_failed']);

        $this->auditService->log('paddle_payment_failed', $subscription ?? $deal, [
            'invoice_id' => $invoice?->id,
            'paddle_transaction_id' => $transactionId,
            'error' => $reason,
        ]);
    }

    // ──────────────────────────────────────────────
    // Mirroring Paddle's subscription onto the deal (and the portal Subscription)
    // ──────────────────────────────────────────────

    /**
     * Apply a Paddle subscription entity (webhook `data` or an API response). Creates no
     * notifications on the first sync of a deal (the deal-paid emails cover that), and
     * afterwards notifies only about what actually CHANGED — so a webhook replay, or the
     * webhook that follows an admin action, sends nothing twice.
     *
     * @param string $origin 'paddle' (webhook / poll / a change made in Paddle's dashboard) or
     *   'admin' (an action taken in this portal — the admin doesn't need an alert about their own click)
     */
    public function syncSubscription(array $paddleSub, string $origin = 'paddle', ?string $reason = null): ?Deal
    {
        $paddleId = $paddleSub['id'] ?? null;

        if (!$paddleId) {
            return null;
        }

        $deal = Deal::where('paddle_subscription_id', $paddleId)->first();

        // subscription.created arrives BEFORE the transaction that would link the deal to it —
        // Paddle copies the transaction's custom_data (our deal uuid) onto the subscription.
        if (!$deal && ($reference = $paddleSub['custom_data']['reference'] ?? null)) {
            $deal = Deal::where('uuid', $reference)->first();
        }

        $subscription = Subscription::where('paddle_subscription_id', $paddleId)->first();
        $deal ??= $subscription?->deal;

        if (!$deal && !$subscription) {
            return null; // Not one of ours
        }

        // Demo company: skip only a PASSIVE sync (a webhook, or the reconcile sweep, which never
        // fetches a demo deal in the first place — this is what actually protects the webhook
        // path). An admin's own deliberate action ($origin 'admin' — Start billing now, Cancel,
        // Move first charge…) must still sync its result, or that deal's own page would be stuck
        // showing a stale status forever after the admin's click.
        if ($origin === 'paddle' && $this->isDemo($deal, $subscription)) {
            return $deal;
        }

        $state = $this->extractState($paddleSub);

        $before = $deal ? [
            'status' => $deal->paddle_status,
            'trial_ends_at' => $deal->trial_ends_at,
            'scheduled_change' => $deal->paddle_scheduled_change,
        ] : null;

        $firstSync = !$deal || $deal->paddle_status === null;

        $deal?->update([
            'paddle_subscription_id' => $paddleId,
            'paddle_status' => $state['status'],
            'trial_ends_at' => $state['trial_ends_at'],
            'next_billed_at' => $state['next_billed_at'],
            'paddle_period_start' => $state['period_start'],
            'paddle_period_end' => $state['period_end'],
            'paddle_scheduled_change' => $state['scheduled_action'],
            'paddle_scheduled_change_at' => $state['scheduled_at'],
            'paddle_canceled_at' => $state['canceled_at'],
        ]);

        if ($subscription) {
            $this->syncPortalSubscription($subscription, $state, $reason);
        }

        if ($deal && !$firstSync) {
            $this->announceChanges($deal->fresh(), $before, $state, $origin, hasSubscription: $subscription !== null);
        }

        return $deal;
    }

    protected function extractState(array $sub): array
    {
        $status = $sub['status'] ?? null;
        $item = $sub['items'][0] ?? [];
        $next = BillingTime::fromPaddle($sub['next_billed_at'] ?? null);
        $scheduled = $sub['scheduled_change'] ?? null;

        return [
            'status' => $status,
            // While trialing, the first real charge is the trial's end (== next_billed_at).
            'trial_ends_at' => $status === 'trialing'
                ? (BillingTime::fromPaddle($item['trial_dates']['ends_at'] ?? null) ?? $next)
                : null,
            'next_billed_at' => $next,
            'period_start' => BillingTime::fromPaddle($sub['current_billing_period']['starts_at'] ?? null),
            'period_end' => BillingTime::fromPaddle($sub['current_billing_period']['ends_at'] ?? null),
            'scheduled_action' => $scheduled['action'] ?? null,
            'scheduled_at' => BillingTime::fromPaddle($scheduled['effective_at'] ?? null),
            'canceled_at' => BillingTime::fromPaddle($sub['canceled_at'] ?? null),
        ];
    }

    /**
     * State the portal Subscription must follow (as opposed to what anyone is told about it):
     * its trial end tracks Paddle's first-charge date, and a Paddle cancellation cancels it.
     */
    protected function syncPortalSubscription(Subscription $subscription, array $state, ?string $reason): void
    {
        if ($state['status'] === 'trialing' && $subscription->is_trial && $state['trial_ends_at']) {
            $current = $subscription->trial_ends_at;

            if (!$current || abs($current->diffInSeconds($state['trial_ends_at'])) > 60) {
                $subscription->update([
                    'trial_ends_at' => $state['trial_ends_at'],
                    'current_period_end' => BillingTime::businessDay($state['trial_ends_at']),
                    'expires_at' => $state['trial_ends_at'],
                    'trial_ending_warned' => false,
                ]);
            }
        }

        if ($state['status'] === 'canceled' && $subscription->status !== 'cancelled') {
            // Force: Paddle's cancellation is authoritative, whatever invoices are open here.
            // Voids unpaid invoices, pauses the assistant and emails the customer. Paddle has
            // already cancelled by now, so a failure here must not be swallowed silently NOR strand
            // the two systems out of step: the admin is told, and reconcile retries every run.
            try {
                $this->subscriptionService->cancel($subscription, $reason ?: 'Cancelled in Paddle', force: true);
            } catch (\Throwable $e) {
                report($e);

                $deal = $subscription->deal;

                $this->emailService->notifyAdmins(
                    type: 'admin_portal_out_of_sync',
                    subject: 'Cancelled in Paddle, but the portal couldn\'t cancel it' . ($deal ? ": {$deal->business_name}" : ''),
                    headline: 'A cancellation needs your attention',
                    intro: 'Paddle has cancelled this subscription, so nothing further will be charged — but the portal subscription could not be cancelled and is still active. The portal will retry automatically; if it keeps failing, cancel it by hand. Error: ' . $e->getMessage(),
                    facts: array_filter(['Customer' => $deal?->business_name, 'Assistant' => $subscription->agent?->name]),
                    ctaUrl: $deal ? route('deals.show', $deal) : null,
                    ctaLabel: 'Open deal',
                    tone: 'danger',
                    deal: $deal
                );
            }
        }
    }

    /**
     * Turn detected changes into notifications. Only reached after a deal's FIRST sync.
     */
    protected function announceChanges(Deal $deal, array $before, array $state, string $origin, bool $hasSubscription): void
    {
        $fromPaddle = $origin === 'paddle';
        $facts = [
            'Customer' => "{$deal->business_name} ({$deal->customer_name})",
            'Plan' => DealTerms::summary($deal),
        ];
        $ctaUrl = route('deals.show', $deal);

        // ── Status changes
        if ($state['status'] === 'canceled' && $before['status'] !== 'canceled') {
            // With an assistant attached, SubscriptionService::cancel() already emailed the customer.
            if (!$hasSubscription) {
                $this->emailService->sendDealBillingUpdate($deal, 'cancelled');
            }

            if ($fromPaddle) {
                $this->emailService->notifyAdmins(
                    type: 'admin_subscription_cancelled',
                    subject: "Subscription cancelled: {$deal->business_name}",
                    headline: 'A subscription was cancelled',
                    intro: "The Paddle subscription for {$deal->business_name} is now cancelled, so nothing further will be charged."
                        . ($hasSubscription ? ' The portal subscription has been cancelled and the assistant deactivated.' : ''),
                    facts: $facts,
                    ctaUrl: $ctaUrl,
                    ctaLabel: 'Open deal',
                    tone: 'danger',
                    deal: $deal
                );
            }

            return;
        }

        if ($fromPaddle && $state['status'] === 'past_due' && $before['status'] !== 'past_due') {
            $this->emailService->notifyAdmins(
                type: 'admin_payment_past_due',
                subject: "Payment past due: {$deal->business_name}",
                headline: 'A subscription payment failed',
                intro: "Paddle couldn't collect the retainer for {$deal->business_name} and the subscription is now past due. Paddle retries automatically and emails the customer; you may want to reach out.",
                facts: $facts + ['Amount' => DealTerms::money($deal->agreed_monthly_price) . '/mo'],
                ctaUrl: $ctaUrl,
                ctaLabel: 'Open deal',
                tone: 'danger',
                deal: $deal
            );
        }

        $wasPaused = $before['status'] === 'paused';
        $isPaused = $state['status'] === 'paused';

        if ($fromPaddle && $wasPaused !== $isPaused) {
            $verb = $isPaused ? 'paused' : 'resumed';

            $this->emailService->notifyAdmins(
                type: 'admin_billing_change',
                subject: "Subscription {$verb}: {$deal->business_name}",
                headline: "Subscription {$verb} in Paddle",
                intro: "The Paddle subscription for {$deal->business_name} was {$verb}.",
                facts: $facts,
                ctaUrl: $ctaUrl,
                ctaLabel: 'Open deal',
                tone: 'warning',
                deal: $deal
            );
        }

        // ── The first-charge date moved (an extension, or a shortened trial)
        if ($state['status'] === 'trialing' && $before['status'] === 'trialing'
            && $before['trial_ends_at'] && $state['trial_ends_at']
            && abs($before['trial_ends_at']->diffInSeconds($state['trial_ends_at'])) > 60) {
            $this->emailService->sendDealBillingUpdate($deal, 'trial_changed', $before['trial_ends_at']);

            if ($fromPaddle) {
                $this->emailService->notifyAdmins(
                    type: 'admin_billing_change',
                    subject: "Trial date changed in Paddle: {$deal->business_name}",
                    headline: 'A trial date was changed in Paddle',
                    intro: "The first-charge date for {$deal->business_name} moved from " . BillingTime::date($before['trial_ends_at']) . ' to ' . BillingTime::date($state['trial_ends_at']) . '. The customer has been told.',
                    facts: $facts + ['New first charge' => BillingTime::dateTime($state['trial_ends_at'])],
                    ctaUrl: $ctaUrl,
                    ctaLabel: 'Open deal',
                    deal: $deal
                );
            }
        }

        // ── A scheduled cancellation was set, or withdrawn
        if ($before['scheduled_change'] !== $state['scheduled_action']) {
            if ($state['scheduled_action'] === 'cancel') {
                $this->emailService->sendDealBillingUpdate($deal, 'cancel_scheduled');

                if ($fromPaddle) {
                    $this->emailService->notifyAdmins(
                        type: 'admin_billing_change',
                        subject: "Cancellation scheduled: {$deal->business_name}",
                        headline: 'A cancellation was scheduled in Paddle',
                        intro: "{$deal->business_name} is set to cancel on " . BillingTime::date($deal->paddle_scheduled_change_at) . ' with no further charges. You can undo this from the deal page until then.',
                        facts: $facts,
                        ctaUrl: $ctaUrl,
                        ctaLabel: 'Open deal',
                        tone: 'warning',
                        deal: $deal
                    );
                }
            } elseif ($before['scheduled_change'] === 'cancel') {
                $this->emailService->sendDealBillingUpdate($deal, 'cancel_withdrawn');
            }
        }
    }

    /**
     * Re-read the subscription from Paddle and apply it. For after-payment, polling and the
     * admin "Sync" button.
     *
     * @throws \App\Exceptions\PaddleApiException
     */
    public function refreshDeal(Deal $deal, string $origin = 'paddle'): Deal
    {
        if (!$deal->paddle_subscription_id) {
            throw new \InvalidArgumentException('This deal has no Paddle subscription to sync.');
        }

        return $this->syncSubscription($this->paddle->getSubscription($deal->paddle_subscription_id), $origin) ?? $deal;
    }

    protected function refreshQuietly(Deal $deal): void
    {
        try {
            $this->refreshDeal($deal);
        } catch (\Throwable $e) {
            // Not fatal — the next reconcile run re-reads it. The payment itself is already recorded.
            Log::warning("Couldn't sync Paddle subscription for deal {$deal->uuid}: {$e->getMessage()}");
        }
    }

    // ──────────────────────────────────────────────
    // Admin actions — each calls Paddle, then re-syncs from the response, so the portal's
    // view updates immediately (and the notifications come from the detected change).
    // ──────────────────────────────────────────────

    /**
     * Move the first-charge date of a trialing subscription — a client wants a day or two more,
     * the date lands on a weekend, or it's being held until the assistant goes live.
     *
     * @throws \InvalidArgumentException|\App\Exceptions\PaddleApiException
     */
    public function moveFirstCharge(Deal $deal, Carbon $newChargeAt, ?string $reason = null): Deal
    {
        $this->assertManageable($deal, ['trialing'], 'The charge date can only be moved while the subscription is still in its free trial.');

        if ($newChargeAt->lt(now()->addMinutes(self::MIN_CHARGE_LEAD_MINUTES))) {
            throw new \InvalidArgumentException('The new charge date must be at least 30 minutes from now.');
        }

        $before = $deal->trial_ends_at;

        $deal = $this->syncSubscription(
            $this->paddle->setNextBilledAt($deal->paddle_subscription_id, $newChargeAt),
            'admin'
        ) ?? $deal;

        // Human-readable (billing timezone) because this is what the activity timeline prints.
        $this->auditService->log('paddle_trial_extended', $deal, [
            'from' => $before ? BillingTime::dateTime($before) : null,
            'to' => BillingTime::dateTime($deal->trial_ends_at),
            'reason' => $reason,
        ]);

        return $deal;
    }

    /**
     * End the trial now: Paddle charges the card immediately. The charge itself is recorded by
     * the transaction.completed webhook (or the next reconcile) like any other.
     */
    public function startBillingNow(Deal $deal): Deal
    {
        $this->assertManageable($deal, ['trialing'], 'Billing can only be started early for a subscription that is still in its free trial.');

        $deal = $this->syncSubscription($this->paddle->activateSubscription($deal->paddle_subscription_id), 'admin') ?? $deal;

        $this->auditService->log('paddle_billing_started', $deal, ['reason' => 'Trial ended early by an admin']);

        // The charge is happening right now; make the next reconcile look for it even if it checked recently.
        Cache::forget(self::chargeCheckKey($deal));

        return $deal;
    }

    /**
     * Cancel: at the end of the trial / paid period (nothing further charged, service runs
     * until then) or immediately.
     */
    public function cancelSubscription(Deal $deal, bool $immediately, ?string $reason = null): Deal
    {
        $this->assertManageable($deal, ['trialing', 'active', 'past_due', 'paused'], 'This subscription can no longer be cancelled.');

        if (!$immediately && $deal->hasScheduledCancellation()) {
            throw new \InvalidArgumentException('A cancellation is already scheduled. Undo it first, or cancel immediately.');
        }

        $deal = $this->syncSubscription(
            $this->paddle->cancelSubscription($deal->paddle_subscription_id, $immediately),
            'admin',
            $reason
        ) ?? $deal;

        $this->auditService->log($immediately ? 'paddle_cancelled_immediately' : 'paddle_cancel_scheduled', $deal, ['reason' => $reason]);

        return $deal;
    }

    /** Withdraw a scheduled cancellation. */
    public function undoScheduledCancellation(Deal $deal): Deal
    {
        $this->assertManageable($deal, ['trialing', 'active', 'past_due', 'paused'], 'This subscription is already cancelled.');

        if (!$deal->hasScheduledCancellation()) {
            throw new \InvalidArgumentException('There is no scheduled cancellation to undo.');
        }

        $deal = $this->syncSubscription($this->paddle->removeScheduledChange($deal->paddle_subscription_id), 'admin') ?? $deal;

        $this->auditService->log('paddle_cancel_withdrawn', $deal);

        return $deal;
    }

    protected function assertManageable(Deal $deal, array $statuses, string $message): void
    {
        if (!$deal->paddle_subscription_id) {
            throw new \InvalidArgumentException('This deal has no Paddle subscription.');
        }

        if (!in_array($deal->paddle_status, $statuses, true)) {
            throw new \InvalidArgumentException($message);
        }
    }

    // ──────────────────────────────────────────────
    // Scheduled work
    // ──────────────────────────────────────────────

    /**
     * Remind the customer AND the admin that a trial's first charge is coming. Runs daily; a
     * deal is reminded once per charge date, so moving the date re-arms it.
     */
    public function sendTrialReminders(?int $days = null): int
    {
        $sent = 0;

        foreach (Deal::trialReminderDue($days ?? (int) config('billing.trial_reminder_days', 3))->with('closer')->get() as $deal) {
            try {
                // One transaction: the customer email, the admin alert and the "reminded" mark stand or
                // fall together, so a failure part-way can't leave the customer emailed twice tomorrow.
                DB::transaction(function () use ($deal) {
                    $this->emailService->sendDealTrialEnding($deal);

                    $daysLeft = BillingTime::daysUntil($deal->nextChargeAt());
                    $weekend = BillingTime::isWeekend($deal->nextChargeAt());

                    $this->emailService->notifyAdmins(
                        type: 'admin_trial_ending',
                        subject: "Trial ends in {$daysLeft} day(s): {$deal->business_name}",
                        headline: 'A free trial is about to convert',
                        intro: "The card on file for {$deal->business_name} will be charged " . DealTerms::money($deal->agreed_monthly_price)
                            . ' on ' . BillingTime::dateTime($deal->nextChargeAt()) . '. The customer has been reminded. If they asked for more time, want to cancel, or the date is inconvenient'
                            . ($weekend ? ' (it falls on a weekend)' : '') . ', change it from the deal page before then; otherwise nothing needs doing.',
                        facts: [
                            'Customer' => "{$deal->business_name} ({$deal->customer_name})",
                            'First charge' => BillingTime::dateTime($deal->nextChargeAt()),
                            'Amount' => DealTerms::money($deal->agreed_monthly_price),
                        ],
                        ctaUrl: route('deals.show', $deal),
                        ctaLabel: 'Manage billing',
                        tone: 'warning',
                        deal: $deal,
                        alsoNotify: $this->closerEmail($deal)
                    );

                    $deal->update(['trial_reminded_for' => $deal->trial_ends_at]);
                });

                $sent++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $sent;
    }

    /**
     * The safety net: ask Paddle what actually happened and apply anything the webhooks didn't
     * deliver. Every step is the same idempotent code the webhooks use.
     *
     * @return array{deals_paid: int, synced: int, charges: int, overdue: int, errors: int}
     */
    public function reconcile(): array
    {
        $stats = ['deals_paid' => 0, 'synced' => 0, 'charges' => 0, 'overdue' => 0, 'errors' => 0];

        // Unpaid deals whose transaction Paddle may have completed (a missed transaction.completed
        // webhook would otherwise leave a paying customer unprovisioned), plus every live subscription.
        $deals = Deal::where(function ($q) {
            $q->where(fn ($q) => $q->whereIn('status', ['sent', 'expired'])
                ->whereNotNull('paddle_transaction_id')
                ->where('created_at', '>=', now()->subDays(30)));
            $q->orWhere(fn ($q) => $q->where('status', 'paid')
                ->whereNotNull('paddle_subscription_id')
                ->where(fn ($q) => $q->whereNull('paddle_status')->orWhere('paddle_status', '!=', 'canceled')));
            // Cancelled in Paddle but the portal subscription is still active — retry cancelling it.
            $q->orWhere(fn ($q) => $q->where('paddle_status', 'canceled')
                ->whereHas('subscription', fn ($s) => $s->where('status', '!=', 'cancelled')));
            // A paid recurring deal that never got its subscription id (paid before that was captured,
            // or the id was missing from the webhook): recover it from the deal's own transaction.
            $q->orWhere(fn ($q) => $q->where('status', 'paid')
                ->where('billing_mode', Deal::MODE_RECURRING)
                ->whereNull('paddle_subscription_id')
                ->whereNotNull('paddle_transaction_id')
                ->where('created_at', '>=', now()->subDays(90)));
        })
            // Demo companies are fully excluded from this sweep — see isDemo(). A deal with no
            // company yet (not paid) can't be demo, since the flag only exists on a Company.
            ->where(fn ($q) => $q->whereNull('company_id')->orWhereHas('company', fn ($c) => $c->where('is_demo', false)))
            ->get();

        foreach ($deals as $deal) {
            try {
                $result = $this->reconcileDeal($deal);

                $stats['deals_paid'] += (int) $result['paid'];
                $stats['synced'] += (int) $result['synced'];
                $stats['charges'] += $result['charges'];
                $stats['overdue'] += (int) $result['overdue'];
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::warning("paddle:reconcile — deal {$deal->uuid} failed: {$e->getMessage()}");
            }
        }

        return $stats;
    }

    /**
     * Reconcile one deal against Paddle: has its checkout been paid, what state is its
     * subscription in, and has a charge come due that we haven't recorded? Also behind the
     * admin "Sync with Paddle" button ($origin 'admin').
     *
     * @return array{paid: bool, synced: bool, charges: int, overdue: bool} overdue: the charge date has
     *   passed by more than the grace period and Paddle still hasn't billed it (see flagOverdueCharge)
     */
    public function reconcileDeal(Deal $deal, string $origin = 'paddle'): array
    {
        $result = ['paid' => false, 'synced' => false, 'charges' => 0, 'overdue' => false];

        if (in_array($deal->status, ['sent', 'expired'], true) && $deal->paddle_transaction_id) {
            $txn = $this->paddle->getTransaction($deal->paddle_transaction_id);

            if (($txn['status'] ?? null) === 'completed') {
                $this->processCompletedTransaction($txn);
                $result['paid'] = true;
                $deal->refresh();
            }
        }

        if ($deal->status === 'paid' && $deal->hasRecurring() && !$deal->paddle_subscription_id && $deal->paddle_transaction_id) {
            $subscriptionId = $this->paddle->getTransaction($deal->paddle_transaction_id)['subscription_id'] ?? null;

            if ($subscriptionId) {
                $deal->update(['paddle_subscription_id' => $subscriptionId]);
            }
        }

        if ($deal->paddle_status === 'canceled' && $deal->subscription && $deal->subscription->status !== 'cancelled') {
            $this->syncPortalSubscription($deal->subscription, ['status' => 'canceled'], 'Cancelled in Paddle');
            $result['synced'] = true;
        }

        if ($deal->status === 'paid' && $deal->paddle_subscription_id && $deal->paddle_status !== 'canceled') {
            $chargeMayBeDue = $this->shouldCheckForCharges($deal);

            $this->refreshDeal($deal, $origin);
            $result['synced'] = true;

            if ($chargeMayBeDue) {
                // Paddle lists newest first. Recorded oldest first instead, so each charge carries the
                // subscription forward in turn — newest-first would jump straight to the latest period
                // and leave every earlier one arriving "late". Routed through processCompletedTransaction()
                // (not processSubscriptionCharge() directly) so a missed usage-overage charge — origin
                // subscription_charge — is recognised and applied the same way a webhook would, not
                // silently skipped by isRetainerCharge()'s exclusion of that origin.
                foreach (array_reverse($this->paddle->listSubscriptionTransactions($deal->paddle_subscription_id)) as $txn) {
                    if (($txn['id'] ?? null) !== $deal->paddle_transaction_id && $this->processCompletedTransaction($txn)) {
                        $result['charges']++;
                    }
                }
            }

            // Last, and from the mirror as just refreshed: a charge date that is STILL past on a fresh read,
            // after anything Paddle did charge has been recorded above, is one nobody collected.
            $result['overdue'] = $this->flagOverdueCharge($deal->fresh());
        }

        return $result;
    }

    /**
     * Whether to list a subscription's recent transactions looking for a charge whose webhook
     * never arrived. Always when the mirror is empty or the charge date has passed; for a billing
     * subscription at most once per CHARGE_CHECK_MINUTES (one extra API call per live deal). The
     * date test alone isn't enough: after "Start billing now" the mirror has already moved the next
     * charge a month ahead, yet that just-taken charge may be the one nobody recorded.
     */
    protected function shouldCheckForCharges(Deal $deal): bool
    {
        if ($deal->paddle_status === null || ($deal->next_billed_at && $deal->next_billed_at->isPast())) {
            return true;
        }

        return in_array($deal->paddle_status, ['active', 'past_due', 'paused'], true)
            && Cache::add(self::chargeCheckKey($deal), true, now()->addMinutes(self::CHARGE_CHECK_MINUTES));
    }

    protected static function chargeCheckKey(Deal $deal): string
    {
        return "paddle:charge-check:{$deal->id}";
    }

    /**
     * A trialing, active or past-due subscription whose charge date passed more than the grace period
     * ago (Deal::isChargeOverdue) means Paddle hasn't billed it, the payment keeps failing, or it billed
     * something nobody recorded.
     * Only ever called on a FRESH read of Paddle (a stale mirror would cry wolf), and alerts the admin
     * ONCE per charge date: charge_overdue_alerted_for remembers which, and the next period re-arms it.
     * The customer is not told and the assistant keeps running — pausing service over what may be a
     * Paddle-side delay is the admin's call, not the system's.
     *
     * @return bool whether the charge is overdue right now (whether or not this call was the one to alert)
     */
    protected function flagOverdueCharge(?Deal $deal): bool
    {
        if (!$deal || !$deal->isChargeOverdue()) {
            return false;
        }

        $overdue = false;

        // One transaction: the audit entry, the admin email and the "already alerted" mark stand or fall
        // together, so a failure part-way can't leave the alert repeating on every run.
        DB::transaction(function () use ($deal, &$overdue) {
            // Locked so two reconcile runs (or a run and the admin's Sync click) can't both alert, and
            // re-checked on the locked row because the date may have moved since the caller looked.
            $locked = Deal::whereKey($deal->id)->lockForUpdate()->first();

            if (!$locked->isChargeOverdue()) {
                return;
            }

            $overdue = true;
            $dueAt = $locked->next_billed_at;

            if ($locked->charge_overdue_alerted_for?->eq($dueAt)) {
                return; // Already alerted for this charge date
            }

            $daysLate = abs(BillingTime::daysUntil($dueAt) ?? 0);
            $amount = DealTerms::money($locked->agreed_monthly_price);
            $pastDue = $locked->paddle_status === 'past_due';

            $this->auditService->log('paddle_charge_overdue', $locked, [
                'reason' => 'Charge date ' . BillingTime::date($dueAt) . " passed {$daysLate} day(s) ago with no charge recorded; Paddle still shows the subscription as " . str_replace('_', ' ', $locked->paddle_status) . '.',
            ]);

            $this->emailService->notifyAdmins(
                type: 'admin_charge_overdue',
                subject: "Charge overdue: {$locked->business_name}",
                headline: 'A Paddle charge is overdue',
                intro: $pastDue
                    ? "Paddle has been unable to collect {$amount} from {$locked->business_name} since " . BillingTime::dateTime($dueAt) . " ({$daysLate} day(s) ago) — the subscription is past due and no charge has been recorded. "
                        . 'Paddle keeps retrying and emails the customer about the failed payment; this portal has not contacted them, and the assistant has been left running. '
                        . 'You may want to reach out, or check the subscription in Paddle.'
                    : "Paddle was due to charge {$locked->business_name} {$amount} on " . BillingTime::dateTime($dueAt) . " — {$daysLate} day(s) ago — but no charge has happened or been recorded, "
                        . "and the subscription still shows as {$locked->paddle_status} in Paddle. The assistant has been left running and the customer has not been contacted. "
                        . 'Check the subscription in Paddle (the card on file, a billing hold, a Paddle incident), and use Sync with Paddle on the deal page once it is sorted.',
                facts: [
                    'Customer' => "{$locked->business_name} ({$locked->customer_name})",
                    'Amount' => $amount . '/mo',
                    'Was due' => BillingTime::dateTime($dueAt),
                    'Paddle status' => $locked->paddle_status_label,
                ],
                ctaUrl: route('deals.show', $locked),
                ctaLabel: 'Open deal',
                tone: 'danger',
                deal: $locked
            );

            $locked->update(['charge_overdue_alerted_for' => $dueAt]);
        });

        return $overdue;
    }

    // ──────────────────────────────────────────────
    // Admin alerts
    // ──────────────────────────────────────────────

    protected function alertDealPaid(Deal $deal, bool $wasVoided): void
    {
        $facts = array_filter([
            'Customer' => "{$deal->business_name} ({$deal->customer_name})",
            'Email' => $deal->email,
            'Paid today' => $deal->dueToday() > 0 ? DealTerms::money($deal->dueToday()) : null,
            'Deal' => DealTerms::summary($deal),
            'First charge' => $deal->isTrialing() ? BillingTime::dateTime($deal->trial_ends_at) : null,
        ]);

        $next = match (true) {
            $deal->isRetainerDeal() => 'Next: create the subscription for this customer\'s assistant so usage is billed against this retainer.',
            $deal->isSetupOnly() => 'Next: build the assistant in Retell and add it to the deal. The monthly retainer is agreed later — use "Add monthly retainer" on the deal when it is.',
            default => 'Next: build the assistant in Retell, then add it to the deal (Add Assistant) so the portal starts tracking it.',
        };

        // A trial-only retainer takes no payment at checkout — "paid $0.00" would just be confusing.
        $tookPayment = $deal->dueToday() > 0;

        $this->emailService->notifyAdmins(
            type: 'admin_deal_paid',
            subject: $tookPayment
                ? "Deal paid: {$deal->business_name} — " . DealTerms::money($deal->dueToday())
                : "Trial started: {$deal->business_name} — " . DealTerms::summary($deal),
            headline: $tookPayment ? 'A deal was paid' : 'A trial was started',
            intro: "{$deal->customer_name} completed checkout for {$deal->business_name}"
                . ($tookPayment ? '.' : ' (card saved, nothing charged yet).')
                . ' ' . ($deal->isRetainerDeal() ? '' : 'The customer account and login invitation are set up. ') . $next
                . ($wasVoided ? ' Note: this deal had been voided before they paid.' : ''),
            facts: $facts,
            ctaUrl: route('deals.show', $deal),
            ctaLabel: 'Open deal',
            tone: 'success',
            deal: $deal,
            alsoNotify: $this->closerEmail($deal)
        );
    }

    protected function alertChargeCollected(Deal $deal, bool $isFirstCharge, bool $noAssistantAttached): void
    {
        if ($noAssistantAttached) {
            $this->emailService->notifyAdmins(
                type: 'admin_charge_unattached',
                subject: "Charge collected, no assistant attached: {$deal->business_name}",
                headline: 'Paddle charged a customer with no assistant attached',
                intro: "Paddle collected " . DealTerms::money($deal->agreed_monthly_price) . " from {$deal->business_name}, but no assistant has been added to the deal yet, so the portal has no subscription to track usage against. The payment is recorded; add the assistant so it's adopted.",
                facts: ['Customer' => "{$deal->business_name} ({$deal->customer_name})", 'Amount' => DealTerms::money($deal->agreed_monthly_price)],
                ctaUrl: route('deals.show', $deal),
                ctaLabel: 'Open deal',
                tone: 'warning',
                deal: $deal
            );

            return;
        }

        if ($isFirstCharge) {
            $this->emailService->notifyAdmins(
                type: 'admin_first_charge_collected',
                subject: "First charge collected: {$deal->business_name} — " . DealTerms::money($deal->agreed_monthly_price),
                headline: 'Trial converted — first charge collected',
                intro: "Paddle charged {$deal->business_name} " . DealTerms::money($deal->agreed_monthly_price) . ' and the portal has recorded the invoice as paid. Monthly billing continues automatically.',
                facts: array_filter([
                    'Customer' => "{$deal->business_name} ({$deal->customer_name})",
                    'Amount' => DealTerms::money($deal->agreed_monthly_price),
                    'Next charge' => $deal->next_billed_at?->isFuture() ? BillingTime::dateTime($deal->next_billed_at) : null,
                ]),
                ctaUrl: route('deals.show', $deal),
                ctaLabel: 'Open deal',
                tone: 'success',
                deal: $deal
            );
        }
    }

    /** The owning closer's email when they're not an admin (admins are already covered). */
    protected function closerEmail(Deal $deal): array
    {
        $closer = $deal->closer;

        return $closer && $closer->role !== 'admin' && $closer->email ? [$closer->email] : [];
    }

    protected function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return [$parts[0] ?? $fullName, $parts[1] ?? ''];
    }
}
