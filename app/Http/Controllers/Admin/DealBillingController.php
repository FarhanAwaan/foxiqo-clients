<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PaddleApiException;
use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Services\PaddleLifecycleService;
use App\Support\BillingTime;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The admin controls for a Paddle deal's billing: move the first charge, start billing now,
 * cancel (at the end of the trial or immediately), undo a scheduled cancellation, and sync
 * with Paddle. Each is a thin wrapper — the rules and the Paddle calls live in
 * PaddleLifecycleService, and every notification comes from the change it detects.
 */
class DealBillingController extends Controller
{
    public function __construct(
        protected PaddleLifecycleService $lifecycle
    ) {}

    /** "+N days" or an exact date, keeping the current first-charge clock time. */
    public function extend(Request $request, Deal $deal): RedirectResponse
    {
        $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (!$request->filled('days') && !$request->filled('date')) {
            return back()->with('error', 'Choose how many days to add, or pick a date.');
        }

        if (!$deal->trial_ends_at) {
            return back()->with('error', 'This deal has no first-charge date yet. Use "Sync with Paddle" first.');
        }

        // Work in the billing display timezone so "+2 days" keeps the wall-clock time the
        // customer sees, even across a daylight-saving change.
        $tz = BillingTime::timezone();
        $current = $deal->trial_ends_at->copy()->timezone($tz);

        $newChargeAt = $request->filled('date')
            ? Carbon::createFromFormat('Y-m-d', $request->input('date'), $tz)->setTime($current->hour, $current->minute)
            : $current->copy()->addDays((int) $request->input('days'));

        return $this->attempt($deal, function () use ($deal, $newChargeAt, $request) {
            $updated = $this->lifecycle->moveFirstCharge($deal, $newChargeAt, $request->input('reason'));

            return 'First charge moved to ' . BillingTime::dateTime($updated->trial_ends_at) . '. The customer has been emailed.';
        });
    }

    public function activate(Deal $deal): RedirectResponse
    {
        return $this->attempt($deal, function () use ($deal) {
            $this->lifecycle->startBillingNow($deal);

            return 'Billing started — Paddle is charging the card on file now. The invoice appears here as soon as Paddle confirms the payment.';
        });
    }

    public function cancel(Request $request, Deal $deal): RedirectResponse
    {
        $request->validate([
            'mode' => ['required', 'in:scheduled,immediately'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $immediately = $request->input('mode') === 'immediately';

        return $this->attempt($deal, function () use ($deal, $immediately, $request) {
            $updated = $this->lifecycle->cancelSubscription($deal, $immediately, $request->input('reason'));

            return $immediately
                ? 'Subscription cancelled. Nothing further will be charged and the customer has been emailed.'
                : 'Cancellation scheduled for ' . BillingTime::date($updated->paddle_scheduled_change_at) . ' — nothing further will be charged, and the customer has been emailed. You can undo it until then.';
        });
    }

    public function resume(Deal $deal): RedirectResponse
    {
        return $this->attempt($deal, function () use ($deal) {
            $this->lifecycle->undoScheduledCancellation($deal);

            return 'Cancellation undone — the subscription carries on as normal, and the customer has been emailed.';
        });
    }

    /** Pull the latest from Paddle now: subscription state, and any payment/charge we haven't recorded. */
    public function sync(Deal $deal): RedirectResponse
    {
        return $this->attempt($deal, function () use ($deal) {
            $result = $this->lifecycle->reconcileDeal($deal, 'admin');

            if ($result['paid']) {
                return 'Paddle shows this deal as paid — the customer account has been set up.';
            }

            // "Everything is up to date" would be false when Paddle itself still hasn't charged what was due.
            if ($result['overdue']) {
                return "Synced with Paddle, but the charge that was due still hasn't happened — see the warning on this page."
                    . ($result['charges'] > 0 ? " Recorded {$result['charges']} charge(s) that had been missed." : '');
            }

            return $result['charges'] > 0
                ? "Synced with Paddle and recorded {$result['charges']} charge(s) that had been missed."
                : 'Synced with Paddle — everything is up to date.';
        });
    }

    /**
     * Runs an action and turns the two expected failures into a flash message: a business-rule
     * problem (wrong state, date too soon) and Paddle refusing the call (its message is written
     * for humans, e.g. "no payment method on file").
     */
    protected function attempt(Deal $deal, callable $action): RedirectResponse
    {
        // Back to wherever the admin clicked from — the deal page, or the assistant's subscription page.
        $back = fn () => redirect()->to(url()->previous(route('deals.show', $deal)));

        try {
            return $back()->with('success', $action());
        } catch (\InvalidArgumentException|PaddleApiException $e) {
            return $back()->with('error', $e->getMessage());
        }
    }
}
