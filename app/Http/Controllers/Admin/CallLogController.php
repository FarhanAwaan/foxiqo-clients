<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CallLogController extends Controller
{
    public function index(Agent $agent, Request $request): View
    {
        $agent->load(['company', 'subscription.plan']);

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

        return view('admin.agents.calls.index', compact('agent', 'callLogs'));
    }
}
