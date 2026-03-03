<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\CallLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CallLogController extends Controller
{
    public function index(Agent $agent, Request $request): View|JsonResponse
    {
        $this->authorizeAgent($agent);

        $query = $agent->callLogs()->latest('started_at');

        if ($request->filled('from_date')) {
            $query->whereDate('started_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('started_at', '<=', $request->to_date);
        }

        if ($request->filled('phone')) {
            $phone = $request->phone;
            $query->where(function ($q) use ($phone) {
                $q->where('from_number', 'like', "%{$phone}%")
                  ->orWhere('to_number', 'like', "%{$phone}%");
            });
        }

        $callLogs = $query->paginate(20)->withQueryString();

        if ($request->boolean('refresh')) {
            return response()->json([
                'rows_html'      => view('customer.calls._rows', compact('callLogs'))->render(),
                'total'          => $callLogs->total(),
                'pagination_html'=> $callLogs->hasPages() ? $callLogs->links()->render() : null,
                'showing_text'   => $callLogs->hasPages()
                    ? "Showing {$callLogs->firstItem()} to {$callLogs->lastItem()} of {$callLogs->total()} calls"
                    : null,
            ]);
        }

        return view('customer.calls.index', compact('agent', 'callLogs'));
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
