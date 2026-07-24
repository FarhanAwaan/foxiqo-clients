<?php

namespace App\Services\Calendar;

use App\Models\CalendarConnection;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleCalendarProvider implements CalendarProviderInterface
{
    protected const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    protected const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    protected const API_BASE = 'https://www.googleapis.com/calendar/v3';
    protected const SCOPE = 'https://www.googleapis.com/auth/calendar.events';

    public function key(): string
    {
        return 'google';
    }

    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id' => SystemSetting::getValue('google_calendar_client_id', ''),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return self::AUTH_URL . '?' . $query;
    }

    public function exchangeCodeForTokens(string $code, string $redirectUri): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => SystemSetting::getValue('google_calendar_client_id', ''),
            'client_secret' => SystemSetting::getValue('google_calendar_client_secret', ''),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Google token exchange failed: ' . $response->body());
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_at' => Carbon::now()->addSeconds($data['expires_in'])->toIso8601String(),
            'calendar_id' => 'primary',
        ];
    }

    public function createEvent(CalendarConnection $connection, array $eventData): string
    {
        $accessToken = $this->ensureFreshAccessToken($connection);
        $calendarId = $connection->credentials['calendar_id'] ?? 'primary';

        $response = Http::withToken($accessToken)
            ->post(self::API_BASE . "/calendars/{$calendarId}/events", [
                'summary' => $eventData['title'],
                'description' => $eventData['description'] ?? null,
                'start' => ['dateTime' => $eventData['starts_at']->toRfc3339String()],
                'end' => ['dateTime' => $eventData['ends_at']->toRfc3339String()],
                'attendees' => array_filter([
                    $eventData['attendee_email'] ? [
                        'email' => $eventData['attendee_email'],
                        'displayName' => $eventData['attendee_name'] ?? null,
                    ] : null,
                ]),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Google Calendar event creation failed: ' . $response->body());
        }

        return $response->json('id');
    }

    public function cancelEvent(CalendarConnection $connection, string $externalEventId): void
    {
        $accessToken = $this->ensureFreshAccessToken($connection);
        $calendarId = $connection->credentials['calendar_id'] ?? 'primary';

        $response = Http::withToken($accessToken)
            ->delete(self::API_BASE . "/calendars/{$calendarId}/events/{$externalEventId}");

        if ($response->failed() && $response->status() !== 404 && $response->status() !== 410) {
            throw new RuntimeException('Google Calendar event cancellation failed: ' . $response->body());
        }
    }

    protected function ensureFreshAccessToken(CalendarConnection $connection): string
    {
        $credentials = $connection->credentials;
        $expiresAt = isset($credentials['expires_at']) ? Carbon::parse($credentials['expires_at']) : null;

        if ($expiresAt && $expiresAt->isFuture()) {
            return $credentials['access_token'];
        }

        if (empty($credentials['refresh_token'])) {
            throw new RuntimeException("Google Calendar connection for agent {$connection->agent_id} has no refresh token and its access token expired.");
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => SystemSetting::getValue('google_calendar_client_id', ''),
            'client_secret' => SystemSetting::getValue('google_calendar_client_secret', ''),
            'refresh_token' => $credentials['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            $connection->update(['status' => 'error', 'last_error' => $response->body()]);
            throw new RuntimeException('Google Calendar token refresh failed: ' . $response->body());
        }

        $data = $response->json();
        $credentials['access_token'] = $data['access_token'];
        $credentials['expires_at'] = Carbon::now()->addSeconds($data['expires_in'])->toIso8601String();

        $connection->update(['credentials' => $credentials]);

        return $credentials['access_token'];
    }
}
