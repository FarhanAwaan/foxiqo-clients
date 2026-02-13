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
        $response = Http::withToken($this->apiKey)->get("{$this->baseUrl}/v2/get-call/{$callId}");

        return $response->json();
    }

    public function processWebhook(WebhookLog $webhookLog): void
    {
        $payload = $webhookLog->payload;
        $eventType = $payload['event'] ?? null;
        $callData = $payload['call'] ?? [];
        $callId = $callData['call_id'] ?? null;

        if (!$callId) {
            throw new \Exception('Invalid webhook payload: missing call_id');
        }

        // Use pre-validated agent from URL-based authentication
        $agentId = $payload['_agent_id'] ?? null;
        $agent = $agentId ? Agent::find($agentId) : null;

        // Fallback to retell_agent_id lookup for backward compatibility
        if (!$agent && isset($callData['agent_id'])) {
            $agent = Agent::where('retell_agent_id', $callData['agent_id'])->first();
        }

        if (!$agent) {
            throw new \Exception("Agent not found for webhook: call_id={$callId}");
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
        $callLog = CallLog::where('retell_call_id', $callData['call_id'])->where('call_status', 'started')->first();

        if (!$callLog) {
            $callLog = $this->handleCallStarted($agent, $callData);
        }

        $durationSeconds = isset($callData['duration_ms'])
            ? (int) round($callData['duration_ms'] / 1000)
            : null;

        $callLog->update([
            'call_status' => 'ended',
            'ended_at' => now(),
            'duration_seconds' => $durationSeconds,
            'duration_minutes' => $durationSeconds !== null
                ? round($durationSeconds / 60, 2)
                : null,
            'retell_cost' => $callData['call_cost']['combined_cost'] ?? null,
            'transcript' => isset($callData['transcript_object']) ? json_encode($callData['transcript_object']) : null,
            'metadata' => $callData,
        ]);
    }

    protected function handleCallAnalyzed(Agent $agent, array $callData): void
    {
        $callLog = CallLog::where('retell_call_id', $callData['call_id'])->first();

        if (!$callLog) {
            $callLog = $this->handleCallStarted($agent, $callData);
        }

        $durationSeconds = isset($callData['duration_ms'])
            ? (int) round($callData['duration_ms'] / 1000)
            : $callLog->duration_seconds;

        $callLog->update([
            'call_status'       => 'analyzed',
            'ended_at'          => $callLog->ended_at ?? now(),
            'duration_seconds'  => $durationSeconds,
            'duration_minutes'  => $durationSeconds !== null ? round($durationSeconds / 60, 2) : $callLog->duration_minutes,
            'retell_cost'       => isset($callData['call_cost']['combined_cost']) ? (int) round($callData['call_cost']['combined_cost'] / 100) : $callLog->retell_cost,
            'transcript'        => isset($callData['transcript_object']) ? json_encode($this->formatRetellTranscript($callData['transcript_object'])) : $callLog->transcript,
            'summary'           => $callData['call_analysis']['call_summary'] ?? $callLog->summary,
            'sentiment'         => $callData['call_analysis']['user_sentiment'] ?? $callLog->sentiment,
            'recording_url'     => $callData['recording_url'] ?? $callLog->recording_url,
            'analyzed_at'       => now(),
            'metadata'          => $callData,
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

    /**
    * Format Retell transcript before saving to DB
    */
    public function formatRetellTranscript(?array $transcriptObject): ?array
    {
        if (empty($transcriptObject)) {
            return null;
        }

        $formatted = [];

        foreach ($transcriptObject as $entry) {
            if (!isset($entry['role']) || !isset($entry['content'])) {
                continue;
            }

            $formatted[] = [
                'speaker' => $entry['role'],
                'message' => $entry['content'],
            ];
        }

        return $formatted;
    }
}
