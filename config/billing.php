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
        'bank_name' => env('BANK_NAME'),
        'bank_address' => env('BANK_ADDRESS'),
        'account_holder' => env('ACCOUNT_HOLDER_NAME'),
        'account_number' => env('ACCOUNT_NUMBER'),
        'routing_number' => env('ROUTING_NUMBER'),
        'account_type' => env('ACCOUNT_TYPE', 'Checking'),
    ]
];

