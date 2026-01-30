<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRetellWebhook;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RetellWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $webhookLog = WebhookLog::create([
            'source' => 'retell',
            'event_type' => $request->input('event', 'unknown'),
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
            'status' => 'received',
        ]);

        ProcessRetellWebhook::dispatch($webhookLog);

        return response('OK', 200);
    }
}
