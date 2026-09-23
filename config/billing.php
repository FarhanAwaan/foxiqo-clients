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
    ]
];

