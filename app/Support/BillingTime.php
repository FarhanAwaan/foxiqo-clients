<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * The one place Paddle timestamps enter and leave the portal.
 *
 * Paddle speaks UTC. The app stores DATETIME columns in config('app.timezone') (a
 * Carbon assigned to a column is written in ITS OWN zone, so a UTC Carbon would be
 * silently re-read as app-timezone — hence fromPaddle()). Customers and admins both
 * see billing dates in ONE display timezone, so an email saying "charged on Oct 9"
 * always matches the deal page.
 */
class BillingTime
{
    public static function fromPaddle(?string $iso): ?Carbon
    {
        return $iso ? Carbon::parse($iso)->setTimezone(config('app.timezone')) : null;
    }

    public static function toPaddle(CarbonInterface $at): string
    {
        return $at->copy()->utc()->format('Y-m-d\TH:i:s\Z');
    }

    public static function timezone(): string
    {
        return config('billing.display_timezone', 'America/New_York');
    }

    /**
     * The calendar day an instant falls on for the business, as a value for a DATE column:
     * midnight in the APP timezone carrying that day's Y-m-d. A Carbon assigned to a column is
     * written as its own wall clock and read back as app time, so startOfDay() in the app zone
     * stores the wrong day whenever the two zones straddle midnight — Oct 24, 20:24 UTC is already
     * Oct 25 in Karachi, but it is Oct 24, 4:24 PM in New York, the day date() shows the customer.
     */
    public static function businessDay(CarbonInterface $at): Carbon
    {
        $local = $at->copy()->timezone(self::timezone());

        return Carbon::create($local->year, $local->month, $local->day, 0, 0, 0, config('app.timezone'));
    }

    /** The last moment of that business day, as the instant it is (kept in the app zone like any DATETIME). */
    public static function endOfBusinessDay(CarbonInterface $at): Carbon
    {
        return $at->copy()->timezone(self::timezone())->endOfDay()->timezone(config('app.timezone'));
    }

    /** "October 9, 2026" */
    public static function date(?CarbonInterface $at): string
    {
        return $at ? $at->copy()->timezone(self::timezone())->format('F j, Y') : '—';
    }

    /** "Fri, Oct 9, 2026 · 3:55 PM EDT" — the weekday matters when a charge lands on a weekend. */
    public static function dateTime(?CarbonInterface $at): string
    {
        return $at ? $at->copy()->timezone(self::timezone())->format('D, M j, Y · g:i A T') : '—';
    }

    public static function isWeekend(?CarbonInterface $at): bool
    {
        return $at !== null && $at->copy()->timezone(self::timezone())->isWeekend();
    }

    /** Whole days from now until $at in the display timezone (0 = today), so "in 3 days" matches the calendar. */
    public static function daysUntil(?CarbonInterface $at): ?int
    {
        if (!$at) {
            return null;
        }

        $tz = self::timezone();

        return (int) now($tz)->startOfDay()->diffInDays($at->copy()->timezone($tz)->startOfDay(), false);
    }
}
