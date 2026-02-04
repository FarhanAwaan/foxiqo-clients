<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with(['user', 'company']);

        // Filter by company
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by action type
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by entity type
        if ($request->filled('entity_type')) {
            $query->where('entity_type', 'like', '%' . $request->entity_type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        // Order chronologically (oldest first for timeline) or reverse
        $order = $request->get('order', 'desc');
        $logs = $query->orderBy('created_at', $order)->paginate(50)->withQueryString();

        // Get filter options
        $companies = Company::orderBy('name')->get();
        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');
        $entityTypes = AuditLog::select('entity_type')
            ->whereNotNull('entity_type')
            ->distinct()
            ->get()
            ->map(fn($log) => class_basename($log->entity_type))
            ->unique()
            ->sort()
            ->values();

        // Group logs by date for timeline view
        $groupedLogs = $logs->getCollection()->groupBy(function ($log) {
            return $log->created_at->format('Y-m-d');
        });

        return view('admin.audit-logs.index', compact(
            'logs',
            'groupedLogs',
            'companies',
            'actions',
            'entityTypes'
        ));
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load(['user', 'company']);

        return view('admin.audit-logs.show', compact('auditLog'));
    }
}
