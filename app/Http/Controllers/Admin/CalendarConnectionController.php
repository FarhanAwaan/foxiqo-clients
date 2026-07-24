<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\CalendarConnection;
use App\Services\AuditService;
use App\Services\Calendar\GoogleCalendarProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CalendarConnectionController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function connectGoogle(Agent $agent, GoogleCalendarProvider $provider): RedirectResponse
    {
        $redirectUri = route('admin.calendar.google.callback');
        $state = encrypt($agent->id);

        return redirect($provider->getAuthorizationUrl($redirectUri, $state));
    }

    public function googleCallback(Request $request, GoogleCalendarProvider $provider): RedirectResponse
    {
        $agent = Agent::findOrFail(decrypt($request->query('state')));

        if ($request->has('error')) {
            return redirect()->route('admin.agents.show', $agent)
                ->with('error', 'Google Calendar connection was cancelled.');
        }

        $tokens = $provider->exchangeCodeForTokens(
            $request->query('code'),
            route('admin.calendar.google.callback')
        );

        CalendarConnection::updateOrCreate(
            ['agent_id' => $agent->id],
            [
                'provider' => 'google',
                'credentials' => $tokens,
                'status' => 'active',
                'connected_at' => now(),
                'last_error' => null,
            ]
        );

        $this->auditService->log('calendar_connected', $agent);

        return redirect()->route('admin.agents.show', $agent)
            ->with('success', 'Google Calendar connected.');
    }

    public function connectCalCom(Request $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validate([
            'api_key' => ['required', 'string'],
            'event_type_id' => ['required', 'integer'],
        ]);

        CalendarConnection::updateOrCreate(
            ['agent_id' => $agent->id],
            [
                'provider' => 'cal_com',
                'credentials' => $validated,
                'status' => 'active',
                'connected_at' => now(),
                'last_error' => null,
            ]
        );

        $this->auditService->log('calendar_connected', $agent);

        return redirect()->route('admin.agents.show', $agent)
            ->with('success', 'Cal.com connected.');
    }

    public function disconnect(Agent $agent): RedirectResponse
    {
        $agent->calendarConnection?->delete();

        $this->auditService->log('calendar_disconnected', $agent);

        return redirect()->route('admin.agents.show', $agent)
            ->with('success', 'Calendar disconnected.');
    }
}
