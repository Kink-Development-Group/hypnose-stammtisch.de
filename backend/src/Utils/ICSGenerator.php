<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use Carbon\Carbon;
use HypnoseStammtisch\Config\Config;

/**
 * ICS Calendar generation utility
 * Generates RFC 5545 compliant iCalendar feeds using manual string generation
 * for maximum compatibility with Hetzner hosting
 */
class ICSGenerator
{
    private const DOMAIN = 'hypnose-stammtisch.de';

    /**
     * Retrieve the configured application name with fallback for ICS metadata.
     */
    private static function getAppName(): string
    {
        return Config::get('app.name', 'Hypnose Stammtisch');
    }

    /**
     * Generate ICS content for a single event
     */
    public static function generateSingleEvent(array $eventData): string
    {
        $events = [self::formatEvent($eventData)];
        return self::createICSContent($events);
    }

    /**
     * Generate ICS feed for multiple events
     */
    public static function generateCalendarFeed(array $events): string
    {
        $formattedEvents = array_map([self::class, 'formatEvent'], $events);
        return self::createICSContent($formattedEvents);
    }

    /**
     * Create ICS content with events
     */
    private static function createICSContent(array $events): string
    {
        $lines = [];
        $appName = self::getAppName();
        $escapedAppName = self::escapeValue($appName);

        // Calendar header
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//' . $escapedAppName . '//Calendar 1.0//DE';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';
        $lines[] = 'X-WR-CALNAME:' . self::escapeValue($appName . ' Events');
        $lines[] = 'X-WR-CALDESC:' . self::escapeValue($appName . ' community calendar for hypnosis meetups and workshops');
        $lines[] = 'X-WR-TIMEZONE:Europe/Berlin';

        // Timezone definition
        $lines = array_merge($lines, self::getTimezoneDefinition());

        // Add events
        foreach ($events as $event) {
            $lines = array_merge($lines, $event);
        }

        // Calendar footer
        $lines[] = 'END:VCALENDAR';

        return self::foldLines($lines);
    }

    /**
     * Format a single event for ICS
     */
    private static function formatEvent(array $event): array
    {
        $timezone = $event['timezone'] ?? 'Europe/Berlin';
        $lines = [];

        $startTime = Carbon::parse($event['start_datetime'], $timezone);
        $endTime = Carbon::parse($event['end_datetime'], $timezone);

        // Event start
        $lines[] = 'BEGIN:VEVENT';

        // UID - unique identifier
        $uid = isset($event['parent_event_id'])
            ? 'event-' . $event['parent_event_id'] . '-' . $startTime->format('Ymd') . '@' . self::DOMAIN
            : 'event-' . $event['id'] . '@' . self::DOMAIN;
        $lines[] = 'UID:' . $uid;

        // DTSTAMP - creation timestamp
        $lines[] = 'DTSTAMP:' . Carbon::now('UTC')->format('Ymd\THis\Z');

        // Dates and times
        if (!empty($event['is_all_day'])) {
            $lines[] = 'DTSTART;VALUE=DATE:' . $startTime->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:' . $endTime->addDay()->format('Ymd');
        } else {
            $lines[] = 'DTSTART;TZID=' . $timezone . ':' . $startTime->format('Ymd\THis');
            $lines[] = 'DTEND;TZID=' . $timezone . ':' . $endTime->format('Ymd\THis');
        }

        // Basic properties
        $lines[] = 'SUMMARY:' . self::escapeValue($event['title']);

        if (!empty($event['description'])) {
            $description = self::createEventDescription($event);
            $lines[] = 'DESCRIPTION:' . self::escapeValue($description);
        }

        // Location
        if (!empty($event['location_name']) || !empty($event['location_address'])) {
            $location = self::formatLocation($event);
            $lines[] = 'LOCATION:' . self::escapeValue($location);
        }

        // Organizer
        if (!empty($event['organizer_email'])) {
            $organizer = 'MAILTO:' . $event['organizer_email'];
            if (!empty($event['organizer_name'])) {
                $organizer = 'CN=' . self::escapeValue($event['organizer_name']) . ':' . $organizer;
            }
            $lines[] = 'ORGANIZER:' . $organizer;
        }

        // URL
        $eventUrl = 'https://' . self::DOMAIN . '/events/' . $event['id'];
        $lines[] = 'URL:' . $eventUrl;

        // Categories/tags
        if (!empty($event['tags'])) {
            $tags = is_string($event['tags']) ? json_decode($event['tags'], true) : $event['tags'];
            if (is_array($tags) && !empty($tags)) {
                $lines[] = 'CATEGORIES:' . implode(',', array_map([self::class, 'escapeValue'], $tags));
            }
        }

        // Status
        $status = match ($event['status'] ?? 'published') {
            'cancelled' => 'CANCELLED',
            'draft' => 'TENTATIVE',
            default => 'CONFIRMED'
        };
        $lines[] = 'STATUS:' . $status;

        // Timestamps
        if (!empty($event['created_at'])) {
            $createdAt = Carbon::parse($event['created_at']);
            $lines[] = 'CREATED:' . $createdAt->utc()->format('Ymd\THis\Z');
        }

        if (!empty($event['updated_at'])) {
            $updatedAt = Carbon::parse($event['updated_at']);
            $lines[] = 'LAST-MODIFIED:' . $updatedAt->utc()->format('Ymd\THis\Z');
        }

        // Sequence number for updates
        $lines[] = 'SEQUENCE:0';

        // Event end
        $lines[] = 'END:VEVENT';

        return $lines;
    }

    /**
     * Get timezone definition for Europe/Berlin
     */
    private static function getTimezoneDefinition(): array
    {
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
            'END:VTIMEZONE',
        ];
    }

    /**
     * Format event location for iCal
     */
    private static function formatLocation(array $event): string
    {
        $location = [];

        if (!empty($event['location_name'])) {
            $location[] = $event['location_name'];
        }

        if (!empty($event['location_address'])) {
            $location[] = $event['location_address'];
        }

        if (!empty($event['online_url']) && $event['location_type'] !== 'physical') {
            $location[] = 'Online: ' . $event['online_url'];
        }

        return implode(', ', $location);
    }

    /**
     * Create detailed event description for iCal
     */
    private static function createEventDescription(array $event): string
    {
        $description = [];

        // Basic description
        if (!empty($event['description'])) {
            $description[] = strip_tags($event['description']);
        }

        // Event details
        if (!empty($event['category'])) {
            $description[] = "\\nKategorie: " . ucfirst($event['category']);
        }

        if (!empty($event['difficulty_level']) && $event['difficulty_level'] !== 'all') {
            $description[] = "Schwierigkeitsgrad: " . ucfirst($event['difficulty_level']);
        }

        if (!empty($event['max_participants'])) {
            $description[] = "Max. Teilnehmer: " . $event['max_participants'];
        }

        // Location details
        if (!empty($event['location_instructions'])) {
            $description[] = "\\nAnfahrt: " . $event['location_instructions'];
        }

        // Requirements
        if (!empty($event['requirements'])) {
            $description[] = "\\nVoraussetzungen: " . $event['requirements'];
        }

        // Safety notes
        if (!empty($event['safety_notes'])) {
            $description[] = "\\nSicherheitshinweise: " . $event['safety_notes'];
        }

        // Preparation notes
        if (!empty($event['preparation_notes'])) {
            $description[] = "\\nVorbereitung: " . $event['preparation_notes'];
        }

        // Contact info
        if (!empty($event['organizer_name']) || !empty($event['organizer_email'])) {
            $description[] = "\\nKontakt:";
            if (!empty($event['organizer_name'])) {
                $description[] = "Name: " . $event['organizer_name'];
            }
            if (!empty($event['organizer_email'])) {
                $description[] = "E-Mail: " . $event['organizer_email'];
            }
        }

        // Event URL
        $description[] = "\\nMehr Informationen: https://" . self::DOMAIN . "/events/" . $event['id'];

        return implode("\\n", $description);
    }

    /**
     * Escape iCal values according to RFC 5545
     */
    private static function escapeValue(string $value): string
    {
        // Escape special characters
        $value = str_replace(['\\', ';', ',', "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', '\\r'], $value);
        return $value;
    }

    /**
     * Fold long lines according to RFC 5545 (max 75 characters)
     */
    private static function foldLines(array $lines): string
    {
        $folded = [];

        foreach ($lines as $line) {
            if (strlen($line) <= 75) {
                $folded[] = $line;
            } else {
                $folded[] = substr($line, 0, 75);
                $remaining = substr($line, 75);

                while (strlen($remaining) > 74) {
                    $folded[] = ' ' . substr($remaining, 0, 74);
                    $remaining = substr($remaining, 74);
                }

                if (strlen($remaining) > 0) {
                    $folded[] = ' ' . $remaining;
                }
            }
        }

        return implode("\r\n", $folded) . "\r\n";
    }

    /**
     * Generate calendar feed with proper HTTP headers
     */
    public static function outputCalendarFeed(array $events, string $filename = 'calendar.ics'): void
    {
        $icsContent = self::generateCalendarFeed($events);

        // Set appropriate headers
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Content-Length: ' . strlen($icsContent));

        echo $icsContent;
    }

    /**
     * Generate single event download with proper HTTP headers
     */
    public static function outputSingleEvent(array $event): void
    {
        $icsContent = self::generateSingleEvent($event);
        $filename = 'event-' . ($event['slug'] ?? $event['id']) . '.ics';

        // Set appropriate headers
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Content-Length: ' . strlen($icsContent));

        echo $icsContent;
    }
}
