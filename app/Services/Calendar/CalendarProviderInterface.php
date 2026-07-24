<?php

namespace App\Services\Calendar;

use App\Models\CalendarConnection;

interface CalendarProviderInterface
{
    /**
     * Machine-readable key stored on CalendarConnection::provider (e.g. 'google', 'cal_com').
     */
    public function key(): string;

    /**
     * Create an event on the connected calendar. Returns the provider's event ID.
     *
     * $eventData: ['title', 'description', 'starts_at' (Carbon), 'ends_at' (Carbon),
     *              'attendee_name', 'attendee_email']
     */
    public function createEvent(CalendarConnection $connection, array $eventData): string;

    /**
     * Cancel a previously created event.
     */
    public function cancelEvent(CalendarConnection $connection, string $externalEventId): void;
}
