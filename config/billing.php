<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the details for payoneer ,
    | related to billing and payment processing.
    | This allows you to easily manage and access these credentials throughout application.
    |
    */

    'payoneer' => [
        'bank_name' => env('PAYONEER_BANK_NAME'),
        'account_holder' => env('PAYONEER_ACCOUNT_HOLDER'),
        'account_number' => env('PAYONEER_ACCOUNT_NUMBER'),
        'routing_number' => env('PAYONEER_ROUTING_NUMBER'),
        'swift_code' => env('PAYONEER_SWIFT_CODE'),
        'account_type' => env('PAYONEER_ACCOUNT_TYPE', 'Checking'),
    ]
];

