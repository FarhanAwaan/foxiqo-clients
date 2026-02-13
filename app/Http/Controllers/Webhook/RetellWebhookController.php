<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRetellWebhook;
use App\Models\Agent;
use App\Models\Company;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RetellWebhookController extends Controller
{
    public function handle(Request $request, string $companyUID, string $agentUID): Response
    {
        try {
            $company = Company::where('uuid', $companyUID)->first();

            if (!$company) {
                throw new \Exception("Company not found: {$companyUID}");
            }

            $agent = Agent::where('uuid', $agentUID)
                ->where('company_id', $company->id)
                ->first();

            if (!$agent) {
                throw new \Exception("Agent not found or does not belong to company: agent={$agentUID}, company={$companyUID}");
            }

            $webhookLog = WebhookLog::create([
                'source' => 'retell',
                'event_type' => $request->input('event', 'unknown'),
                'payload' => array_merge($request->all(), [
                    '_company_uid' => $companyUID,
                    '_agent_uid' => $agentUID,
                    '_company_id' => $company->id,
                    '_agent_id' => $agent->id,
                ]),
                'headers' => $request->headers->all(),
                'status' => 'received',
            ]);

            ProcessRetellWebhook::dispatch($webhookLog);

            return response('OK', 200);
        } catch (\Exception $e) {
            WebhookLog::create([
                'source' => 'retell',
                'event_type' => $request->input('event', 'unknown'),
                'payload' => array_merge($request->all(), [
                    '_company_uid' => $companyUID,
                    '_agent_uid' => $agentUID,
                ]),
                'headers' => $request->headers->all(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return response('OK', 200);
        }
    }
}
