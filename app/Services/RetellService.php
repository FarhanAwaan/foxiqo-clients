<?php

namespace App\Services;

use App\Events\CircuitBreakerTriggered;
use App\Exceptions\WebhookOutOfOrderException;
use App\Models\Agent;
use App\Models\CallLog;
use App\Models\SystemSetting;
use App\Models\WebhookLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RetellService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.retellai.com';

    // Retell disconnection_reason values that indicate the caller was never
    // actually connected to a human/agent conversation.
    protected const MISSED_CALL_DISCONNECTION_REASONS = [
        'dial_no_answer',
        'dial_busy',
        'dial_failed',
        'voicemail_reached',
        'user_declined',
        'error_user_not_joined',
    ];

    public function __construct(
        protected EmailService $emailService,
        protected AppointmentService $appointmentService
    ) {
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
        $callType = $callData['call_type'] ?? null;

        if($callType == 'web_call') {
            throw new \Exception("Web call is not supposed to be logged: call_id={$callId}");
        }

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
        // Find any existing log regardless of status (don't restrict to 'started')
        $callLog = CallLog::where('retell_call_id', $callData['call_id'])->first();

        if (!$callLog) {
            $callLog = $this->handleCallStarted($agent, $callData);
        }

        // If call_analyzed already processed first, do not downgrade the status
        if ($callLog->call_status === 'analyzed') {
            return;
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
            'retell_cost' => isset($callData['call_cost']['combined_cost']) ? round($callData['call_cost']['combined_cost'] / 100, 4) : null,
            'transcript' => isset($callData['transcript_object']) ? json_encode($this->formatRetellTranscript($callData['transcript_object'])) : $callLog->transcript,
            'metadata' => $callData,
        ]);

        if (in_array($callData['disconnection_reason'] ?? null, self::MISSED_CALL_DISCONNECTION_REASONS, true)) {
            $this->handleMissedCall($agent, $callLog->fresh());
        }
    }

    protected function handleMissedCall(Agent $agent, CallLog $callLog): void
    {
        if (!$agent->missed_call_email_alerts_enabled) {
            return;
        }

        $this->emailService->sendMissedCallAlert($callLog);
    }

    protected function handleCallAnalyzed(Agent $agent, array $callData): void
    {
        $callLog = CallLog::where('retell_call_id', $callData['call_id'])->first();

        if (!$callLog) {
            // No call_started or call_ended received yet — create a stub and process
            $callLog = $this->handleCallStarted($agent, $callData);
        } elseif ($callLog->call_status === 'started') {
            // call_ended webhook has not been processed yet — re-queue and wait
            throw new WebhookOutOfOrderException(
                "call_analyzed arrived before call_ended for call_id={$callData['call_id']}"
            );
        }

        $durationSeconds = isset($callData['duration_ms'])
            ? (int) round($callData['duration_ms'] / 1000)
            : $callLog->duration_seconds;

        $callLog->update([
            'call_status'       => 'analyzed',
            'ended_at'          => $callLog->ended_at ?? now(),
            'duration_seconds'  => $durationSeconds,
            'duration_minutes'  => $durationSeconds !== null ? round($durationSeconds / 60, 2) : $callLog->duration_minutes,
            'retell_cost'       => isset($callData['call_cost']['combined_cost']) ? round($callData['call_cost']['combined_cost'] / 100, 4) : $callLog->retell_cost,
            'transcript'        => isset($callData['transcript_object']) ? json_encode($this->formatRetellTranscript($callData['transcript_object'])) : $callLog->transcript,
            'summary'           => $callData['call_analysis']['call_summary'] ?? $callLog->summary,
            'sentiment'         => $callData['call_analysis']['user_sentiment'] ?? $callLog->sentiment,
            'recording_url'     => $callData['recording_url'] ?? $callLog->recording_url,
            'analyzed_at'       => now(),
            'metadata'          => $callData,
        ]);

        $this->updateSubscriptionMinutes($agent, $callLog);
        $this->maybeCreateAppointment($agent, $callLog->fresh(), $callData);
    }

    /**
     * Looks for booking details in Retell's post-call custom analysis data.
     * Agencies configure these field names in the Retell dashboard's
     * "Post-Call Analysis" tab for each agent: appointment_requested (boolean),
     * appointment_date ("YYYY-MM-DD"), appointment_time ("HH:MM", 24h),
     * customer_name, customer_email (optional).
     */
    protected function maybeCreateAppointment(Agent $agent, CallLog $callLog, array $callData): void
    {
        $customData = $callData['call_analysis']['custom_analysis_data'] ?? [];

        $requested = $customData['appointment_requested'] ?? false;
        if (!filter_var($requested, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $date = $customData['appointment_date'] ?? null;
        $time = $customData['appointment_time'] ?? null;

        if (!$date || !$time) {
            return;
        }

        try {
            $startsAt = Carbon::parse("{$date} {$time}");
        } catch (\Throwable $e) {
            Log::warning("Could not parse appointment date/time for call {$callLog->id}: {$date} {$time}");
            return;
        }

        $this->appointmentService->createFromCall($agent, $callLog, [
            'customer_name' => $customData['customer_name'] ?? null,
            'customer_email' => $customData['customer_email'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
        ]);
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
