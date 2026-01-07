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
        return Config::getAppName(true);
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
        $calendarDescription = sprintf(
            '%s community calendar for hypnosis meetups and workshops',
            $appName
        );

        // Calendar header
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//' . $escapedAppName . '//Calendar 1.0//DE';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';
        $lines[] = 'X-WR-CALNAME:' . self::escapeValue($appName . ' Events');
        $lines[] = 'X-WR-CALDESC:' . self::escapeValue($calendarDescription);
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
     * Converts Markdown to readable plain text
     */
    private static function createEventDescription(array $event): string
    {
        $description = [];

        // Basic description - convert Markdown to plain text
        if (!empty($event['description'])) {
            $plainText = self::markdownToPlainText($event['description']);
            // Remove any remaining HTML tags for security
            $plainText = strip_tags($plainText);
            $description[] = $plainText;
        }

        // Event details
        if (!empty($event['category'])) {
            $description[] = "\\nKategorie: " . ucfirst($event['category']);
        }

        if (!empty($event['difficulty_level']) && $event['difficulty_level'] !== 'all') {
            $description[] = "Schwierigkeitsgrad: " . ucfirst($event['difficulty_level']);
        }

        if (!empty($event['max_participants'])) {
            $description[] = "Max. Teilnehmer: " . (int)$event['max_participants'];
        }

        // Location details - convert Markdown
        if (!empty($event['location_instructions'])) {
            $locationInstructions = self::markdownToPlainText($event['location_instructions']);
            $description[] = "\\nAnfahrt: " . strip_tags($locationInstructions);
        }

        // Requirements - convert Markdown
        if (!empty($event['requirements'])) {
            $requirements = self::markdownToPlainText($event['requirements']);
            $description[] = "\\nVoraussetzungen: " . strip_tags($requirements);
        }

        // Safety notes - convert Markdown
        if (!empty($event['safety_notes'])) {
            $safetyNotes = self::markdownToPlainText($event['safety_notes']);
            $description[] = "\\nSicherheitshinweise: " . strip_tags($safetyNotes);
        }

        // Preparation notes - convert Markdown
        if (!empty($event['preparation_notes'])) {
            $prepNotes = self::markdownToPlainText($event['preparation_notes']);
            $description[] = "\\nVorbereitung: " . strip_tags($prepNotes);
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
     * Convert Markdown to readable plain text for ICS descriptions
     * Preserves the semantic meaning while removing Markdown syntax
     */
    private static function markdownToPlainText(string $markdown): string
    {
        if (empty($markdown)) {
            return '';
        }

        $text = $markdown;

        // Convert headers to plain text with newlines
        // # Header -> HEADER (uppercase for emphasis)
        $text = preg_replace('/^#{1,6}\s*(.+)$/m', "\n$1\n", $text);

        // Convert bold **text** or __text__ to *text* (preserve emphasis indicator)
        $text = preg_replace('/\*\*(.+?)\*\*/s', '*$1*', $text);
        $text = preg_replace('/__(.+?)__/s', '*$1*', $text);

        // Convert italic *text* or _text_ - keep as is since single asterisks are readable
        // But ensure we don't double-process already converted bold
        $text = preg_replace('/(?<!\*)_([^_]+)_(?!\*)/s', '$1', $text);

        // Convert links [text](url) to "text (url)"
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '$1 ($2)', $text);

        // Convert inline code `code` to 'code'
        $text = preg_replace('/`([^`]+)`/', "'$1'", $text);

        // Convert code blocks ```code``` to indented text
        $text = preg_replace('/```[\w]*\n?(.*?)```/s', "\n$1\n", $text);

        // Convert unordered lists - * item or - item to "• item"
        $text = preg_replace('/^[\*\-]\s+(.+)$/m', '• $1', $text);

        // Convert ordered lists - 1. item to "1. item" (already readable)
        // No change needed

        // Convert blockquotes > text to "| text"
        $text = preg_replace('/^>\s*(.+)$/m', '| $1', $text);

        // Convert horizontal rules --- or *** or ___ to a line
        $text = preg_replace('/^[\-\*_]{3,}$/m', '---', $text);

        // Remove images ![alt](url) - just keep alt text
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '[$1]', $text);

        // Clean up excessive newlines (more than 2 consecutive)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Trim whitespace
        $text = trim($text);

        return $text;
    }

    /**
     * Fold long lines according to RFC 5545 (max 75 octets/bytes per line)
     * This function is UTF-8 safe and won't break multi-byte characters
     */
    private static function foldLines(array $lines): string
    {
        $folded = [];

        foreach ($lines as $line) {
            $folded[] = self::foldLine($line);
        }

        return implode("\r\n", $folded) . "\r\n";
    }

    /**
     * Fold a single line to max 75 bytes without breaking UTF-8 characters
     * RFC 5545 specifies 75 octets (bytes) per line, not characters
     */
    private static function foldLine(string $line): string
    {
        // If line is already short enough (in bytes), return as-is
        if (strlen($line) <= 75) {
            return $line;
        }

        $result = [];
        $currentLine = '';
        $isFirstLine = true;
        $maxBytes = 75;

        // Process character by character to avoid breaking UTF-8 sequences
        $length = mb_strlen($line, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($line, $i, 1, 'UTF-8');
            $charBytes = strlen($char); // Byte length of this character

            // Calculate the limit for current line
            // First line: 75 bytes, continuation lines: 74 bytes (plus 1 space prefix)
            $limit = $isFirstLine ? 75 : 74;

            // Check if adding this character would exceed the limit
            if (strlen($currentLine) + $charBytes > $limit) {
                // Save current line and start a new one
                $result[] = $currentLine;
                $currentLine = $char;
                $isFirstLine = false;
            } else {
                $currentLine .= $char;
            }
        }

        // Add the last line
        if ($currentLine !== '') {
            $result[] = $currentLine;
        }

        // Join with CRLF and space for continuation lines
        $output = $result[0];
        for ($i = 1; $i < count($result); $i++) {
            $output .= "\r\n " . $result[$i];
        }

        return $output;
    }

    /**
     * Generate calendar feed with proper HTTP headers
     */
    public static function outputCalendarFeed(array $events, string $filename = 'calendar.ics'): void
    {
        $icsContent = self::generateCalendarFeed($events);

        // Ensure content is valid UTF-8
        if (!mb_check_encoding($icsContent, 'UTF-8')) {
            $icsContent = mb_convert_encoding($icsContent, 'UTF-8', 'auto');
        }

        // Add UTF-8 BOM for better compatibility with calendar clients
        // Many clients (Outlook, Apple Calendar) need this to properly detect UTF-8
        $bom = "\xEF\xBB\xBF";
        $icsContent = $bom . $icsContent;

        // Set appropriate headers - explicitly set UTF-8 encoding
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

        // Ensure content is valid UTF-8
        if (!mb_check_encoding($icsContent, 'UTF-8')) {
            $icsContent = mb_convert_encoding($icsContent, 'UTF-8', 'auto');
        }

        // Add UTF-8 BOM for better compatibility with calendar clients
        $bom = "\xEF\xBB\xBF";
        $icsContent = $bom . $icsContent;

        // Set appropriate headers
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Content-Length: ' . strlen($icsContent));

        echo $icsContent;
    }
}
