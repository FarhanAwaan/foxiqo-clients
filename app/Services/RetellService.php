<?php

namespace App\Services;

use App\Events\CircuitBreakerTriggered;
use App\Models\Agent;
use App\Models\CallLog;
use App\Models\SystemSetting;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;

class RetellService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.retellai.com';

    public function __construct()
    {
        $this->apiKey = SystemSetting::getValue('retell_api_key', '');
    }

    public function getCallDetails(string $callId): array
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/v2/get-call/{$callId}");

        return $response->json();
    }

    public function processWebhook(WebhookLog $webhookLog): void
    {
        $payload = $webhookLog->payload;
        $eventType = $payload['event'] ?? null;
        $callData = $payload['call'] ?? [];
        $callId = $callData['call_id'] ?? null;
        $agentId = $callData['agent_id'] ?? null;

        if (!$callId || !$agentId) {
            throw new \Exception('Invalid webhook payload: missing call_id or agent_id');
        }

        $agent = Agent::where('retell_agent_id', $agentId)->first();

        if (!$agent) {
            throw new \Exception("Agent not found: {$agentId}");
        }

        match ($eventType) {
            'call_started' => $this->handleCallStarted($agent, $callData),
            'call_ended' => $this->handleCallEnded($agent, $callData),
            'call_analyzed' => $this->handleCallAnalyzed($agent, $callData),
            default => null
        };

        $webhookLog->markProcessed();
    }

    protected function handleCallStarted(Agent $agent, array $callData): CallLog
    {
        return CallLog::updateOrCreate(
            ['retell_call_id' => $callData['call_id']],
            [
                'agent_id' => $agent->id,
                'call_status' => 'started',
                'direction' => $callData['direction'] ?? null,
                'from_number' => $callData['from_number'] ?? null,
                'to_number' => $callData['to_number'] ?? null,
                'started_at' => now(),
                'metadata' => $callData,
            ]
        );
    }

    protected function handleCallEnded(Agent $agent, array $callData): void
    {
        $callLog = CallLog::where('retell_call_id', $callData['call_id'])->first();

        if (!$callLog) {
            $callLog = $this->handleCallStarted($agent, $callData);
        }

        $callLog->update([
            'call_status' => 'ended',
            'ended_at' => now(),
            'duration_seconds' => $callData['duration_seconds'] ?? null,
            'duration_minutes' => isset($callData['duration_seconds'])
                ? round($callData['duration_seconds'] / 60, 2)
                : null,
        ]);
    }

    protected function handleCallAnalyzed(Agent $agent, array $callData): void
    {
        $callLog = CallLog::where('retell_call_id', $callData['call_id'])->first();

        if (!$callLog) {
            $callLog = $this->handleCallStarted($agent, $callData);
        }

        $callLog->update([
            'call_status' => 'analyzed',
            'ended_at' => $callLog->ended_at ?? now(),
            'duration_seconds' => $callData['duration_seconds'] ?? $callLog->duration_seconds,
            'duration_minutes' => isset($callData['duration_seconds'])
                ? round($callData['duration_seconds'] / 60, 2)
                : $callLog->duration_minutes,
            'retell_cost' => $callData['cost'] ?? null,
            'transcript' => isset($callData['transcript']) ? json_encode($callData['transcript']) : null,
            'summary' => $callData['summary'] ?? null,
            'sentiment' => $callData['sentiment'] ?? null,
            'recording_url' => $callData['recording_url'] ?? null,
            'analyzed_at' => now(),
            'metadata' => $callData,
        ]);

        $this->updateSubscriptionMinutes($agent, $callLog);
    }

    protected function updateSubscriptionMinutes(Agent $agent, CallLog $callLog): void
    {
        $subscription = $agent->subscription;

        if (!$subscription || $subscription->status !== 'active') {
            return;
        }

        $minutes = (int) ceil($callLog->duration_minutes ?? 0);
        $subscription->increment('minutes_used', $minutes);

        // Check circuit breaker
        $threshold = SystemSetting::getValue('circuit_breaker_threshold', 150);
        $limitMinutes = $subscription->plan->included_minutes * ($threshold / 100);

        if ($subscription->minutes_used >= $limitMinutes && !$subscription->circuit_breaker_triggered) {
            $subscription->update([
                'circuit_breaker_triggered' => true,
                'circuit_breaker_triggered_at' => now(),
            ]);

            event(new CircuitBreakerTriggered($subscription));
        }
    }
}
