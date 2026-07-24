<?php

use App\Http\Controllers\Webhook\RetellWebhookController;
use App\Http\Controllers\Webhook\PayoneerWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks')->group(function () {
    // Verifies X-Retell-Signature (see App\Http\Middleware\VerifyWebhookSignature).
    // Company/agent UUIDs in the URL remain the primary routing mechanism either way.
    Route::post('retell/company/{company_uid}/agent/{agent_uid}', [RetellWebhookController::class, 'handle'])
        ->middleware('webhook.verify');

    // Payoneer does not currently have a documented signing scheme wired up here.
    Route::post('payoneer', [PayoneerWebhookController::class, 'handle']);
});
