<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Models\Event;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\ICSGenerator;
use HypnoseStammtisch\Utils\RRuleProcessor;
use HypnoseStammtisch\Config\Config;
use Carbon\Carbon;

/**
 * Calendar controller for ICS feeds
 */
class CalendarController
{
    /**
     * Generate ICS calendar feed
     * GET /api/calendar/feed
     * GET /api/calendar/feed/{token}
     */
    public function feed(string $token = null): void
    {
        try {
            // Validate token if provided
            if ($token && !$this->validateFeedToken($token)) {
                Response::json(['success' => false, 'error' => 'Invalid calendar feed token'], 403);
                return;
            }

            // Get events with expansion for recurring events
            $events = $this->getExpandedEventsForFeed();

            // Generate ICS content using the new ICSGenerator
            $filename = $token ? 'private-calendar.ics' : 'public-calendar.ics';
            ICSGenerator::outputCalendarFeed($events, $filename);

            // Update token access tracking
            if ($token) {
                $this->updateTokenAccess($token);
            }
        } catch (\Exception $e) {
            error_log("Calendar feed error: " . $e->getMessage());
            Response::json(['success' => false, 'error' => 'Failed to generate calendar feed'], 500);
        }
    }

    /**
     * Get calendar metadata
     * GET /api/calendar/meta
     */
    public function meta(): void
    {
        try {
            $stats = $this->getCalendarStats();

            Response::json([
                'success' => true,
                'data' => [
                    'total_events' => $stats['total'],
                    'upcoming_events' => $stats['upcoming'],
                    'categories' => $stats['categories'],
                    'timezone' => Config::get('calendar.timezone', 'Europe/Berlin'),
                    'feed_url' => Config::get('app.url') . '/api/calendar/feed',
                    'last_updated' => date('c')
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Calendar meta error: " . $e->getMessage());
            Response::error('Failed to fetch calendar metadata', 500);
        }
    }

    /**
     * Generate individual event ICS
     * GET /api/calendar/event/{id}/ics
     */
    public function eventIcs(string $id): void
    {
        try {
            $event = Event::findById($id);

            if (!$event || $event->status !== 'published') {
                Response::error('Event not found', 404);
                return;
            }

            $icsContent = $this->generateIcsContent([$event]);

            $filename = $this->sanitizeFilename($event->title) . '.ics';
            Response::ics($icsContent, $filename);
        } catch (\Exception $e) {
            error_log("Event ICS error: " . $e->getMessage());
            Response::error('Failed to generate event ICS', 500);
        }
    }

    /**
     * Generate ICS content from events
     */
    private function generateIcsContent(array $events): string
    {
        $timezone = Config::get('calendar.timezone', 'Europe/Berlin');
        $prodId = '-//Hypnose Stammtisch//Calendar Feed//DE';
        $calName = 'Hypnose Stammtisch';
        $calDesc = 'Events der Hypnose Stammtisch Community';

        $ics = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:' . $prodId,
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' . $calName,
            'X-WR-CALDESC:' . $calDesc,
            'X-WR-TIMEZONE:' . $timezone,
        ];

        // Add timezone definition
        $ics = array_merge($ics, $this->getTimezoneDefinition($timezone));

        // Add events
        foreach ($events as $event) {
            $ics = array_merge($ics, $this->generateEventIcs($event));
        }

        $ics[] = 'END:VCALENDAR';

        return implode("\r\n", $ics) . "\r\n";
    }

    /**
     * Generate ICS for single event
     */
    private function generateEventIcs(Event $event): array
    {
        $timezone = Config::get('calendar.timezone', 'Europe/Berlin');

        // Convert dates to UTC
        $startDt = Carbon::createFromFormat('Y-m-d H:i:s', $event->startDatetime, $timezone);
        $endDt = Carbon::createFromFormat('Y-m-d H:i:s', $event->endDatetime, $timezone);

        $uid = 'event-' . $event->id . '@hypnose-stammtisch.de';
        $timestamp = Carbon::now('UTC')->format('Ymd\THis\Z');

        // Location string
        $location = $this->formatLocation($event);

        // Description
        $description = $this->formatDescription($event);

        // URL
        $url = Config::get('app.frontend_url') . '/events/' . $event->slug;

        $eventIcs = [
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $timestamp,
            'DTSTART;TZID=' . $timezone . ':' . $startDt->format('Ymd\THis'),
            'DTEND;TZID=' . $timezone . ':' . $endDt->format('Ymd\THis'),
            'SUMMARY:' . $this->escapeIcsValue($event->title),
            'DESCRIPTION:' . $this->escapeIcsValue($description),
            'URL:' . $url,
            'CATEGORIES:' . strtoupper($event->category),
            'STATUS:CONFIRMED',
            'TRANSP:OPAQUE'
        ];

        if ($location) {
            $eventIcs[] = 'LOCATION:' . $this->escapeIcsValue($location);
        }

        if ($event->organizerName && $event->organizerEmail) {
            $eventIcs[] = 'ORGANIZER;CN=' . $this->escapeIcsValue($event->organizerName) . ':mailto:' . $event->organizerEmail;
        }

        // Add recurrence rule if event is recurring
        if ($event->isRecurring && $event->rrule) {
            $eventIcs[] = 'RRULE:' . $event->rrule;
        }

        $eventIcs[] = 'END:VEVENT';

        return $eventIcs;
    }

    /**
     * Get timezone definition for ICS
     */
    private function getTimezoneDefinition(string $timezone): array
    {
        // Simplified timezone definition for Europe/Berlin
        if ($timezone === 'Europe/Berlin') {
            return [
                'BEGIN:VTIMEZONE',
                'TZID:Europe/Berlin',
                'BEGIN:DAYLIGHT',
                'TZOFFSETFROM:+0100',
                'TZOFFSETTO:+0200',
                'TZNAME:CEST',
                'DTSTART:19700329T020000',
                'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU',
                'END:DAYLIGHT',
                'BEGIN:STANDARD',
                'TZOFFSETFROM:+0200',
                'TZOFFSETTO:+0100',
                'TZNAME:CET',
                'DTSTART:19701025T030000',
                'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU',
                'END:STANDARD',
                'END:VTIMEZONE'
            ];
        }

        return [];
    }

    /**
     * Format location for ICS
     */
    private function formatLocation(Event $event): string
    {
        $parts = [];

        if ($event->locationName) {
            $parts[] = $event->locationName;
        }

        if ($event->locationAddress) {
            $parts[] = $event->locationAddress;
        }

        if ($event->locationType === 'online' && $event->locationUrl) {
            $parts[] = $event->locationUrl;
        }

        return implode(', ', $parts);
    }

    /**
     * Format description for ICS
     */
    private function formatDescription(Event $event): string
    {
        $parts = [];

        if ($event->description) {
            $parts[] = $event->description;
        }

        if ($event->requirements) {
            $parts[] = "\n\nVoraussetzungen: " . $event->requirements;
        }

        if ($event->safetyNotes) {
            $parts[] = "\n\nSicherheitshinweise: " . $event->safetyNotes;
        }

        if ($event->preparationNotes) {
            $parts[] = "\n\nVorbereitung: " . $event->preparationNotes;
        }

        // Add registration info
        if ($event->requiresRegistration) {
            $parts[] = "\n\nAnmeldung erforderlich.";

            if ($event->registrationDeadline) {
                $deadline = Carbon::createFromFormat('Y-m-d H:i:s', $event->registrationDeadline);
                $parts[] = "Anmeldeschluss: " . $deadline->format('d.m.Y H:i');
            }
        }

        return implode('', $parts);
    }

    /**
     * Escape values for ICS format
     */
    private function escapeIcsValue(string $value): string
    {
        // Remove HTML tags
        $value = strip_tags($value);

        // Escape special characters
        $value = str_replace(['\\', ',', ';', "\n", "\r"], ['\\\\', '\\,', '\\;', '\\n', ''], $value);

        // Limit line length (fold long lines)
        return $this->foldIcsLine($value);
    }

    /**
     * Fold long ICS lines
     */
    private function foldIcsLine(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $folded = substr($line, 0, 75);
        $remainder = substr($line, 75);

        while (strlen($remainder) > 74) {
            $folded .= "\r\n " . substr($remainder, 0, 74);
            $remainder = substr($remainder, 74);
        }

        if (strlen($remainder) > 0) {
            $folded .= "\r\n " . $remainder;
        }

        return $folded;
    }

    /**
     * Validate feed token
     */
    private function validateFeedToken(string $token): bool
    {
        $tokenData = Database::fetchOne(
            "SELECT * FROM calendar_feed_tokens WHERE token = ? AND is_active = 1",
            [$token]
        );

        if (!$tokenData) {
            return false;
        }

        // Check expiration
        if ($tokenData['expires_at'] && strtotime($tokenData['expires_at']) < time()) {
            return false;
        }

        return true;
    }

    /**
     * Update token access tracking
     */
    private function updateTokenAccess(string $token): void
    {
        Database::execute(
            "UPDATE calendar_feed_tokens
             SET last_accessed = NOW(), access_count = access_count + 1
             WHERE token = ?",
            [$token]
        );
    }

    /**
     * Get calendar statistics
     */
    private function getCalendarStats(): array
    {
        $stats = [
            'total' => 0,
            'upcoming' => 0,
            'categories' => []
        ];

        // Total published events
        $total = Database::fetchOne("SELECT COUNT(*) as count FROM events WHERE status = 'published'");
        $stats['total'] = $total['count'] ?? 0;

        // Upcoming events
        $upcoming = Database::fetchOne(
            "SELECT COUNT(*) as count FROM events WHERE status = 'published' AND start_datetime > NOW()"
        );
        $stats['upcoming'] = $upcoming['count'] ?? 0;

        // Categories
        $categories = Database::fetchAll(
            "SELECT category, COUNT(*) as count
             FROM events
             WHERE status = 'published'
             GROUP BY category"
        );

        foreach ($categories as $cat) {
            $stats['categories'][$cat['category']] = $cat['count'];
        }

        return $stats;
    }

    /**
     * Sanitize filename
     */
    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);

        return trim($filename, '-');
    }

    /**
     * Get expanded events for calendar feed including recurring instances
     */
    private function getExpandedEventsForFeed(): array
    {
        // Get base events for the next year
        $baseEvents = Event::getAllPublished([
            'from_date' => date('Y-m-d', strtotime('-1 month')),
            'to_date' => date('Y-m-d', strtotime('+1 year'))
        ]);

        $expandedEvents = [];
        $startDate = Carbon::now()->subMonth();
        $endDate = Carbon::now()->addYear();

        foreach ($baseEvents as $event) {
            $eventArray = $this->eventToArray($event);

            if (!empty($eventArray['is_recurring']) && !empty($eventArray['rrule'])) {
                // Expand recurring event
                try {
                    $instances = RRuleProcessor::expandRecurringEvent(
                        $eventArray,
                        $startDate,
                        $endDate,
                        json_decode($eventArray['exdates'] ?? '[]', true)
                    );
                    $expandedEvents = array_merge($expandedEvents, $instances);
                } catch (\Exception $e) {
                    error_log("Error expanding recurring event {$eventArray['id']}: " . $e->getMessage());
                    // Add the base event if expansion fails
                    $expandedEvents[] = $eventArray;
                }
            } else {
                // Add single event
                $expandedEvents[] = $eventArray;
            }
        }

        return $expandedEvents;
    }

    /**
     * Helper function to convert event to array (handles mock objects)
     */
    private function eventToArray($event): array
    {
        if (method_exists($event, 'toArray')) {
            return $event->toArray();
        } elseif (isset($event->toArray) && is_callable($event->toArray)) {
            return call_user_func($event->toArray);
        } else {
            // Fallback for mock objects
            return (array) $event;
        }
    }
}
