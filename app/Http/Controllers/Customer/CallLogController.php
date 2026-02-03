<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\CallLog;
use Illuminate\View\View;

class CallLogController extends Controller
{
    public function index(Agent $agent): View
    {
        $this->authorizeAgent($agent);

        $calls = $agent->callLogs()->latest()->paginate(15);

        return view('customer.calls.index', compact('agent', 'calls'));
    }

    public function show(CallLog $callLog): View
    {
        $callLog->load('agent');

        $this->authorizeCallLog($callLog);

        return view('customer.calls.show', compact('callLog'));
    }

    protected function authorizeAgent(Agent $agent): void
    {
        if ($agent->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized');
        }
    }

    protected function authorizeCallLog(CallLog $callLog): void
    {
        if ($callLog->agent->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized');
        }
    }
}
