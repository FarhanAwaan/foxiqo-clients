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
        'bank_address' => env('PAYONEER_BANK_ADDRESS'),
        'account_holder' => env('PAYONEER_ACCOUNT_HOLDER'),
        'account_number' => env('PAYONEER_ACCOUNT_NUMBER'),
        'routing_number' => env('PAYONEER_ROUTING_NUMBER'),
        'account_type' => env('PAYONEER_ACCOUNT_TYPE', 'Checking'),
    ]
];

