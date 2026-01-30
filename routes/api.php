<?php

use App\Http\Controllers\Webhook\RetellWebhookController;
use App\Http\Controllers\Webhook\PayoneerWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks')->group(function () {
    Route::post('retell', [RetellWebhookController::class, 'handle']);
    Route::post('payoneer', [PayoneerWebhookController::class, 'handle']);
});
