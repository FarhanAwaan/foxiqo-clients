<?php

namespace App\Support;

/**
 * Paddle amounts arrive as minor-unit strings (cents: "8800" = $88.00), nested under
 * `details.totals` on a transaction or `totals` on an adjustment. This is the one place
 * those get turned into the decimals the portal stores, so every reconciliation figure
 * (Invoice::paddle_charged_amount / paddle_tax_amount / paddle_fee_amount /
 * paddle_refunded_amount) is read the same way.
 */
class PaddleMoney
{
    /** A single minor-unit string ("8800") to a decimal (88.00); null-safe. */
    public static function cents(?string $amount): ?float
    {
        return $amount === null ? null : round(((int) $amount) / 100, 2);
    }

    /**
     * A transaction's `details.totals`. `grand_total` is what the customer's payment method was
     * actually charged (after tax, after any credit applied) — the figure this reconciles against
     * our own `amount`. Falls back to `total` for a payload that omits grand_total.
     *
     * @param array $totals `$txn['details']['totals'] ?? []`
     * @return array{charged: ?float, tax: ?float, fee: ?float}
     */
    public static function fromTransactionTotals(array $totals): array
    {
        return [
            'charged' => self::cents($totals['grand_total'] ?? $totals['total'] ?? null),
            'tax' => self::cents($totals['tax'] ?? null),
            'fee' => self::cents($totals['fee'] ?? null),
        ];
    }

    /**
     * An adjustment's (refund/credit/chargeback) top-level `totals` — no `grand_total` key on this
     * object, `total` is the customer-facing refunded amount.
     *
     * @param array $totals `$payload['data']['totals'] ?? []`
     */
    public static function refundedAmount(array $totals): float
    {
        return self::cents($totals['total'] ?? null) ?? 0.0;
    }
}
