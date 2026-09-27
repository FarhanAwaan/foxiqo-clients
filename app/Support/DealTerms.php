<?php

namespace App\Support;

use App\Models\Deal;
use App\Models\SystemSetting;

/**
 * Describes a deal in words — one source for the checkout page, the emails and the
 * admin alerts, so the customer is never told one thing by the portal and another by
 * Paddle. Prices come from the Deal; dates from Paddle's mirrored first-charge date
 * once it's known ($confirmed), otherwise the trial length ("14 days after checkout").
 */
class DealTerms
{
    public static function money(float|string $amount): string
    {
        return '$' . number_format((float) $amount, 2);
    }

    /**
     * Label/value rows for an order summary.
     *
     * @return array<int, array{0: string, 1: string, 2?: bool}>  [label, value, emphasised]
     */
    public static function rows(Deal $deal, bool $confirmed = false): array
    {
        $rows = [];

        if ((float) $deal->activation_price > 0) {
            $rows[] = ['One-time setup & activation', self::money($deal->activation_price) . ' · today'];
        }

        if ($deal->isSetupOnly()) {
            $rows[] = ['Monthly retainer', 'Agreed after your assistant is set up — we\'ll send a separate link'];
        } elseif ($deal->is_trial) {
            $rows[] = ['Free trial', "{$deal->trial_days} days" . ($confirmed ? '' : ' · starts at checkout')];

            $first = $confirmed && $deal->nextChargeAt()
                ? 'first charge ' . BillingTime::date($deal->nextChargeAt())
                : "starting {$deal->trial_days} days after checkout";

            $rows[] = ['Then, monthly retainer', self::money($deal->agreed_monthly_price) . ' / month · ' . $first];
        } else {
            $rows[] = ['Monthly retainer', self::money($deal->agreed_monthly_price) . ' / month · first month charged today'];
        }

        $rows[] = ['Due today', self::money($deal->dueToday()), true];

        return $rows;
    }

    /** One line for admin lists/alerts, e.g. "$599.00 setup + $497.00/mo after a 14-day trial". */
    public static function summary(Deal $deal): string
    {
        $parts = [];

        if ((float) $deal->activation_price > 0) {
            $parts[] = self::money($deal->activation_price) . ' setup';
        }

        if ($deal->isSetupOnly()) {
            $parts[] = 'retainer to be agreed';
        } else {
            $monthly = self::money($deal->agreed_monthly_price) . '/mo';
            $parts[] = $deal->is_trial ? "{$monthly} after a {$deal->trial_days}-day trial" : $monthly;
        }

        return implode(' + ', $parts);
    }

    public static function supportEmail(): ?string
    {
        return SystemSetting::getValue('company_email') ?: config('mail.from.address');
    }

    public static function firstName(Deal $deal): string
    {
        return preg_split('/\s+/', trim($deal->customer_name))[0] ?: $deal->customer_name;
    }
}
