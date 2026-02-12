<?php

use App\Http\Controllers\Webhook\RetellWebhookController;
use App\Http\Controllers\Webhook\PayoneerWebhookController;
use Illuminate\Support\Facades\Route;

// Secured webhook routes — require X-Webhook-Signature header matching company's signature
Route::prefix('webhooks/{company}')->middleware('webhook.verify')->group(function () {
    Route::post('retell', [RetellWebhookController::class, 'handle']);
});

// Payment provider webhooks (their own verification)
Route::prefix('webhooks')->group(function () {
    Route::post('payoneer', [PayoneerWebhookController::class, 'handle']);
});
