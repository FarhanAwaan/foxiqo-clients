<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Appointment;
use App\Models\BillingCycle;
use App\Models\CalendarConnection;
use App\Models\CallLog;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\PaymentReceipt;
use App\Models\Subscription;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AgentController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $query = Agent::with(['company', 'subscription.plan'])
            ->withCount('callLogs')
            ->withSum('callLogs', 'duration_minutes');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('retell_agent_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $agents = $query->latest()->paginate(12)->withQueryString();
        $companies = Company::orderBy('name')->get();

        return view('admin.agents.index', compact('agents', 'companies'));
    }

    public function create(Request $request): View
    {
        $companies = Company::where('status', 'active')->orderBy('name')->get();

        $selectedCompanyId = $request->integer('company_id') ?: null;
        $deal = $request->filled('deal_id')
            ? Deal::whereKey($request->input('deal_id'))->paid()->whereNull('subscription_id')->first()
            : null;

        return view('admin.agents.create', compact('companies', 'selectedCompanyId', 'deal'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'deal_id' => ['nullable', 'exists:deals,id'],
            'retell_agent_id' => ['required', 'string', 'max:100', 'unique:agents'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'agent_type' => ['required', 'string', 'in:inbound,outbound,both'],
            'cost_per_minute' => ['required', 'numeric', 'min:0'],
            'missed_call_email_alerts_enabled' => ['sometimes', 'boolean'],
            'missed_call_notification_email' => ['nullable', 'email', 'max:255'],
        ]);

        $agent = Agent::create($validated);

        $this->auditService->log('agent_created', $agent);

        return redirect()->route('admin.agents.show', $agent)
            ->with('success', 'Agent created successfully.');
    }

    public function show(Agent $agent, Request $request): View|JsonResponse
    {
        $agent->load(['company', 'subscription.plan', 'calendarConnection']);

        // Get latest 10 calls for the overview (full list available via agents.calls.index)
        $callLogs = $agent->callLogs()
            ->latest('started_at')
            ->limit(10)
            ->get();

        if ($request->boolean('refresh')) {
            return response()->json([
                'rows_html' => view('admin.agents._recent_call_rows', compact('callLogs'))->render(),
            ]);
        }

        $upcomingAppointments = $agent->appointments()->upcoming()->orderBy('starts_at')->limit(10)->get();

        // Calculate stats
        $totalCalls = $agent->callLogs()->count();
        $totalMinutes = $agent->callLogs()->sum('duration_minutes');
        $avgDuration = $totalCalls > 0 ? $agent->callLogs()->avg('duration_seconds') : 0;
        $inboundCalls = $agent->callLogs()->where('direction', 'inbound')->count();
        $outboundCalls = $agent->callLogs()->where('direction', 'outbound')->count();

        $viewData = [
            'isAdmin' => true,
            'recentCallRowsPartial' => 'admin.agents._recent_call_rows',
            'callsIndexUrl' => route('admin.agents.calls.index', $agent),
            'callVolumeUrl' => route('admin.agents.charts.call-volume', $agent),
            'sentimentUrl' => route('admin.agents.charts.sentiment', $agent),
            'companyUrl' => route('admin.companies.show', $agent->company),
            'subscriptionUrl' => $agent->subscription ? route('admin.subscriptions.show', $agent->subscription) : null,
            'createSubscriptionUrl' => route('admin.subscriptions.create', ['agent_id' => $agent->id, 'company_id' => $agent->company_id]),
            'deletionSummary' => $this->deletionSummary($agent, $agent->subscription),
        ];

        return view('admin.agents.show', compact('agent', 'callLogs', 'totalCalls', 'totalMinutes', 'avgDuration', 'inboundCalls', 'outboundCalls', 'upcomingAppointments') + $viewData);
    }

    public function edit(Agent $agent): View
    {
        $companies = Company::where('status', 'active')->orderBy('name')->get();

        return view('admin.agents.edit', compact('agent', 'companies'));
    }

    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'retell_agent_id' => ['required', 'string', 'max:100', 'unique:agents,retell_agent_id,' . $agent->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'agent_type' => ['required', 'string', 'in:inbound,outbound,both'],
            'cost_per_minute' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,paused,archived'],
            'missed_call_email_alerts_enabled' => ['sometimes', 'boolean'],
            'missed_call_notification_email' => ['nullable', 'email', 'max:255'],
        ]);

        $oldValues = $agent->toArray();
        $agent->update($validated);

        $this->auditService->log('agent_updated', $agent, $oldValues);

        return redirect()->route('admin.agents.show', $agent)
            ->with('success', 'Agent updated successfully.');
    }

    /**
     * Permanently delete an assistant and every record tied to it (subscription,
     * invoices/payments/receipts, call logs, appointments, calendar connection,
     * billing history, staff access grants). Requires the admin to type the
     * assistant's exact name — enforced here too, not just in the confirmation
     * modal's JS, since this is irreversible.
     *
     * A Paddle-managed subscription that isn't already cancelled/expired blocks
     * the delete: Paddle would otherwise keep charging the card for a subscription
     * the portal no longer has any record of. Cancel it first (that flow cancels
     * the Paddle side too), then delete.
     *
     * This never touches Retell itself — the agent (and its phone number) stays
     * live there until it's removed in Retell's own console.
     */
    public function destroy(Request $request, Agent $agent): RedirectResponse
    {
        $agent->load('subscription', 'company');
        $subscription = $agent->subscription;

        if ($subscription && $subscription->isPaddleManaged() && !in_array($subscription->status, ['cancelled', 'expired'], true)) {
            return back()->with('error', "This assistant's subscription is Paddle-managed and still {$subscription->status} — Paddle is still billing the card. Cancel the subscription first (from its page, which cancels it in Paddle too), then delete the assistant.");
        }

        if (trim((string) $request->input('confirm_name')) !== $agent->name) {
            return back()->with('error', "Type the assistant's exact name to confirm deletion.");
        }

        $summary = $this->deletionSummary($agent, $subscription);
        $agentName = $agent->name;
        $companyName = $agent->company->name;

        DB::transaction(function () use ($agent, $subscription, $summary, $companyName) {
            $invoiceIds = $subscription
                ? Invoice::where('subscription_id', $subscription->id)->pluck('id')
                : collect();

            // Receipt files live on disk, not just in the DB — clean those up first.
            $receiptFiles = PaymentReceipt::whereIn('invoice_id', $invoiceIds)->pluck('file_path');
            foreach ($receiptFiles as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            PaymentReceipt::whereIn('invoice_id', $invoiceIds)->delete();
            Payment::whereIn('invoice_id', $invoiceIds)->delete();
            PaymentLink::whereIn('invoice_id', $invoiceIds)->delete();
            Invoice::whereIn('id', $invoiceIds)->delete();

            BillingCycle::where('agent_id', $agent->id)->delete();
            Appointment::where('agent_id', $agent->id)->delete();
            CallLog::where('agent_id', $agent->id)->delete();
            CalendarConnection::where('agent_id', $agent->id)->delete();
            DB::table('agent_user_access')->where('agent_id', $agent->id)->delete();

            // Nulls deals.subscription_id automatically if a paid Deal funded this
            // subscription — the deal itself (the sales/payment record) is untouched.
            $subscription?->delete();

            $this->auditService->log('agent_deleted', $agent, [
                'company' => $companyName,
                'retell_agent_id' => $agent->retell_agent_id,
                'deleted_counts' => $summary,
            ]);

            $agent->delete();
        });

        return redirect()->route('admin.agents.index')->with('success',
            "Deleted \"{$agentName}\" ({$companyName}) — {$summary['call_logs']} call log(s), {$summary['invoices']} invoice(s), {$summary['appointments']} appointment(s) and everything else tied to it were permanently removed."
        );
    }

    /**
     * Counts of everything a delete would remove, for the confirmation modal and
     * the audit-log snapshot. `paddle_blocked` mirrors the guard in destroy().
     */
    private function deletionSummary(Agent $agent, ?Subscription $subscription): array
    {
        $invoiceIds = $subscription
            ? Invoice::where('subscription_id', $subscription->id)->pluck('id')
            : collect();

        return [
            'call_logs' => $agent->callLogs()->count(),
            'appointments' => $agent->appointments()->count(),
            'has_subscription' => (bool) $subscription,
            'paddle_managed' => $subscription?->isPaddleManaged() ?? false,
            'paddle_blocked' => $subscription
                ? ($subscription->isPaddleManaged() && !in_array($subscription->status, ['cancelled', 'expired'], true))
                : false,
            'invoices' => $invoiceIds->count(),
            'payments' => Payment::whereIn('invoice_id', $invoiceIds)->count(),
            'payment_links' => PaymentLink::whereIn('invoice_id', $invoiceIds)->count(),
            'payment_receipts' => PaymentReceipt::whereIn('invoice_id', $invoiceIds)->count(),
            'billing_cycles' => BillingCycle::where('agent_id', $agent->id)->count(),
            'has_calendar_connection' => (bool) $agent->calendarConnection,
            'access_grants' => DB::table('agent_user_access')->where('agent_id', $agent->id)->count(),
        ];
    }

    // ── AJAX: Call Volume for this agent ──────────────────────────────
    public function chartCallVolume(Agent $agent, Request $request): JsonResponse
    {
        [$start, $end] = $this->_resolveRange($request);

        $rows = CallLog::where('agent_id', $agent->id)
            ->whereBetween('started_at', [$start, $end])
            ->selectRaw("DATE(started_at) as day, COUNT(*) as cnt")
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('cnt', 'day');

        $labels = [];
        $values = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $day      = $cursor->toDateString();
            $labels[] = $cursor->format('M j');
            $values[] = (int) ($rows[$day] ?? 0);
            $cursor->addDay();
        }

        return response()->json(['labels' => $labels, 'values' => $values]);
    }

    // ── AJAX: Sentiment for this agent ────────────────────────────────
    public function chartSentiment(Agent $agent, Request $request): JsonResponse
    {
        [$start, $end] = $this->_resolveRange($request);

        $row = CallLog::where('agent_id', $agent->id)
            ->whereBetween('started_at', [$start, $end])
            ->whereNotNull('sentiment')
            ->selectRaw("
                SUM(sentiment = 'positive') as positive,
                SUM(sentiment = 'neutral')  as neutral,
                SUM(sentiment = 'negative') as negative
            ")
            ->first();

        return response()->json([
            'positive' => (int) ($row->positive ?? 0),
            'neutral'  => (int) ($row->neutral  ?? 0),
            'negative' => (int) ($row->negative  ?? 0),
        ]);
    }

    private function _resolveRange(Request $request): array
    {
        $now   = Carbon::now();
        $range = $request->input('range', 'last7');

        return match ($range) {
            'today'     => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last30'    => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'custom'    => [
                Carbon::parse($request->input('from', $now->copy()->subDays(6)->toDateString()))->startOfDay(),
                Carbon::parse($request->input('to', $now->toDateString()))->endOfDay(),
            ],
            default     => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
        };
    }
}
