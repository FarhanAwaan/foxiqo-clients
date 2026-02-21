<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Services\RetellService;
use Illuminate\Http\JsonResponse;

class CallLogController extends Controller
{
    public function __construct(
        protected RetellService $retellService
    ) {}

    public function show(CallLog $callLog): JsonResponse
    {
        $callLog->load('agent');

        // Customers can only view their own company's calls
        if (!auth()->user()->isAdmin() && $callLog->agent->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        // Fetch a fresh recording URL from Retell API (stored URLs expire in ~10 minutes)
        $recordingUrl = $callLog->recording_url;
        if ($callLog->retell_call_id) {
            try {
                $retellData = $this->retellService->getCallDetails($callLog->retell_call_id);
                if (!empty($retellData['recording_url'])) {
                    $recordingUrl = $retellData['recording_url'];
                }
            } catch (\Exception $e) {
                // Fall back to stored URL silently
            }
        }

        $data = [
            'uuid' => $callLog->uuid,
            'call_status' => $callLog->call_status,
            'direction' => $callLog->direction,
            'from_number' => $callLog->from_number,
            'to_number' => $callLog->to_number,
            'started_at' => $callLog->started_at?->format('M d, Y h:i A'),
            'ended_at' => $callLog->ended_at?->format('M d, Y h:i A'),
            'duration_formatted' => $callLog->duration_formatted,
            'duration_seconds' => $callLog->duration_seconds,
            'duration_minutes' => $callLog->duration_minutes,
            'sentiment' => $callLog->sentiment,
            'summary' => $callLog->summary,
            'transcript' => $callLog->transcript_array,
            'recording_url' => $recordingUrl,
        ];

        if (auth()->user()->isAdmin()) {
            $data['retell_cost'] = $callLog->retell_cost;
            $data['retell_call_id'] = $callLog->retell_call_id;
        }

        return response()->json($data);
    }
}
