<?php

namespace App\Services\Calendar;

use App\Models\CalendarConnection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CalComProvider implements CalendarProviderInterface
{
    protected const API_BASE = 'https://api.cal.com/v1';

    public function key(): string
    {
        return 'cal_com';
    }

    public function createEvent(CalendarConnection $connection, array $eventData): string
    {
        $apiKey = $connection->credentials['api_key'];
        $eventTypeId = $connection->credentials['event_type_id'];

        $response = Http::post(self::API_BASE . '/bookings', [
            'apiKey' => $apiKey,
            'eventTypeId' => (int) $eventTypeId,
            'start' => $eventData['starts_at']->toIso8601String(),
            'end' => $eventData['ends_at']->toIso8601String(),
            'timeZone' => $eventData['starts_at']->getTimezone()->getName(),
            'language' => 'en',
            'title' => $eventData['title'],
            'description' => $eventData['description'] ?? null,
            'responses' => [
                'name' => $eventData['attendee_name'] ?? 'Caller',
                'email' => $eventData['attendee_email'] ?? 'no-reply@example.com',
            ],
            'metadata' => [],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Cal.com booking creation failed: ' . $response->body());
        }

        return (string) ($response->json('uid') ?? $response->json('id'));
    }

    public function cancelEvent(CalendarConnection $connection, string $externalEventId): void
    {
        $apiKey = $connection->credentials['api_key'];

        $response = Http::delete(self::API_BASE . "/bookings/{$externalEventId}/cancel", [
            'apiKey' => $apiKey,
        ]);

        if ($response->failed() && $response->status() !== 404) {
            throw new RuntimeException('Cal.com booking cancellation failed: ' . $response->body());
        }
    }
}
