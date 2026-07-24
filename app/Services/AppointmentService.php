<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Appointment;
use App\Models\CallLog;
use App\Services\Calendar\CalComProvider;
use App\Services\Calendar\CalendarProviderInterface;
use App\Services\Calendar\GoogleCalendarProvider;
use Illuminate\Support\Facades\Log;

class AppointmentService
{
    /**
     * Create a local appointment record from parsed call data, and sync it to
     * the agent's connected calendar if one exists. The local record is always
     * created — calendar sync is best-effort on top of it.
     */
    public function createFromCall(Agent $agent, ?CallLog $callLog, array $bookingData): Appointment
    {
        $appointment = Appointment::create([
            'company_id' => $agent->company_id,
            'agent_id' => $agent->id,
            'call_log_id' => $callLog?->id,
            'customer_name' => $bookingData['customer_name'] ?? null,
            'customer_phone' => $bookingData['customer_phone'] ?? $callLog?->from_number,
            'customer_email' => $bookingData['customer_email'] ?? null,
            'starts_at' => $bookingData['starts_at'],
            'ends_at' => $bookingData['ends_at'],
            'status' => 'pending_sync',
            'notes' => $bookingData['notes'] ?? null,
        ]);

        $this->syncToCalendar($appointment);

        return $appointment;
    }

    public function syncToCalendar(Appointment $appointment): void
    {
        $connection = $appointment->agent->calendarConnection;

        if (!$connection || !$connection->isActive()) {
            return; // stays pending_sync — no calendar connected yet
        }

        try {
            $provider = $this->resolveProvider($connection->provider);

            $externalId = $provider->createEvent($connection, [
                'title' => "Appointment: {$appointment->customer_name}",
                'description' => $appointment->notes,
                'starts_at' => $appointment->starts_at,
                'ends_at' => $appointment->ends_at ?? $appointment->starts_at->copy()->addMinutes(30),
                'attendee_name' => $appointment->customer_name,
                'attendee_email' => $appointment->customer_email,
            ]);

            $appointment->update([
                'status' => 'synced',
                'provider' => $connection->provider,
                'external_event_id' => $externalId,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Calendar sync failed for appointment {$appointment->id}: {$e->getMessage()}");
            $appointment->update(['status' => 'sync_failed']);
        }
    }

    public function cancel(Appointment $appointment): void
    {
        $connection = $appointment->agent->calendarConnection;

        if ($connection && $connection->isActive() && $appointment->external_event_id) {
            try {
                $this->resolveProvider($appointment->provider)->cancelEvent($connection, $appointment->external_event_id);
            } catch (\Throwable $e) {
                Log::warning("Calendar cancellation failed for appointment {$appointment->id}: {$e->getMessage()}");
            }
        }

        $appointment->update(['status' => 'cancelled']);
    }

    public function resolveProvider(string $key): CalendarProviderInterface
    {
        return match ($key) {
            'google' => app(GoogleCalendarProvider::class),
            'cal_com' => app(CalComProvider::class),
            default => throw new \InvalidArgumentException("Unknown calendar provider: {$key}"),
        };
    }
}
