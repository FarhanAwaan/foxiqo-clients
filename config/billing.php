<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file stores the Nsave bank-transfer details used for the manual
    | bank-transfer / receipt-upload payment rail (Billing\PaymentController).
    | This allows you to easily manage and access these details throughout the application.
    |
    */

    'nsave' => [
        'bank_name' => env('BANK_NAME'),
        'bank_address' => env('BANK_ADDRESS'),
        'account_holder' => env('ACCOUNT_HOLDER_NAME'),
        'account_number' => env('ACCOUNT_NUMBER'),
        'routing_number' => env('ROUTING_NUMBER'),
        'account_type' => env('ACCOUNT_TYPE', 'Checking'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Paddle billing dates
    |--------------------------------------------------------------------------
    |
    | Paddle charges at an exact instant (UTC). Customers and admins see every
    | billing date in this one timezone (App\Support\BillingTime), so an email
    | saying "charged on Oct 9" always matches the deal page. The cron jobs also
    | run on New York time (routes/console.php), so this matches the business clock.
    |
    | trial_reminder_days: how far ahead of a trial's first charge the client and
    | admin are reminded (Paddle also sends its own trial-end reminder to the
    | customer for trials longer than 7 days).
    |
    | charge_overdue_grace_hours: how long past its charge date a trialing/active
    | Paddle subscription may go without a charge before the admin is alerted and
    | Billing & Usage flags it. Paddle bills within hours of the date and a weekend
    | or a Paddle hiccup can delay that, so the alert waits two days.
    |
    | paddle_minimum_charge_cents: below this, a usage charge isn't sent to Paddle at
    | all — Paddle itself refuses a one-time charge under its own minimum (observed:
    | $0.70 USD, error subscription_update_transaction_balance_less_than_charge_limit)
    | and this stays safely above that. A usage invoice this small is left on the
    | normal manual-payment-link path instead of being attempted and failing.
    |
    | combined_receipt_wait_minutes: how long the retainer's own charge receipt waits
    | to see whether a usage-overage charge for the same period also went through, so
    | the customer gets ONE "$497 + $88 = $585 charged" email instead of two separate
    | ones — real sandbox charges settle in a few seconds, so this is generous slack
    | for webhook delivery, not how long a usage charge actually takes. If it isn't
    | resolved by then, the retainer's own receipt goes out alone and the usage charge
    | sends its own receipt whenever it does resolve (ResolvePendingRetainerReceipt).
    |
    */

    'display_timezone' => env('BILLING_DISPLAY_TIMEZONE', 'America/New_York'),

    'trial_reminder_days' => 3,

    'charge_overdue_grace_hours' => 48,

    'paddle_minimum_charge_cents' => 100,

    'combined_receipt_wait_minutes' => 2,
];

