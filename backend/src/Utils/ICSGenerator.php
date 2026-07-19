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
     * The only zone we ship a VTIMEZONE definition for (getTimezoneDefinition()).
     * RFC 5545 §3.2.19 requires a TZID parameter to reference a VTIMEZONE in the
     * same calendar object, so every emitted TZID has to be exactly this.
     */
    private const DEFAULT_TIMEZONE = 'Europe/Berlin';

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
        $timezone = self::resolveTimezone($event['timezone'] ?? null);
        $lines = [];

        $startTime = Carbon::parse($event['start_datetime'], $timezone);
        $endTime = Carbon::parse($event['end_datetime'], $timezone);

        // Event start
        $lines[] = 'BEGIN:VEVENT';

        // UID - unique identifier
        $eventId = self::stripControlChars((string)($event['id'] ?? ''));
        $uid = isset($event['parent_event_id'])
            ? 'event-' . self::stripControlChars((string)$event['parent_event_id'])
                . '-' . $startTime->format('Ymd') . '@' . self::DOMAIN
            : 'event-' . $eventId . '@' . self::DOMAIN;
        $lines[] = 'UID:' . $uid;

        // DTSTAMP - creation timestamp
        $lines[] = 'DTSTAMP:' . Carbon::now('UTC')->format('Ymd\THis\Z');

        // Dates and times
        if (!empty($event['is_all_day'])) {
            // DATE values carry no TZID, so the dates stay in the event's own zone
            $lines[] = 'DTSTART;VALUE=DATE:' . $startTime->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:' . $endTime->addDay()->format('Ymd');
        } else {
            // Normalise to the one zone we define a VTIMEZONE for instead of
            // emitting a dangling TZID; the absolute instant is preserved.
            $lines[] = 'DTSTART;TZID=' . self::DEFAULT_TIMEZONE . ':'
                . $startTime->copy()->setTimezone(self::DEFAULT_TIMEZONE)->format('Ymd\THis');
            $lines[] = 'DTEND;TZID=' . self::DEFAULT_TIMEZONE . ':'
                . $endTime->copy()->setTimezone(self::DEFAULT_TIMEZONE)->format('Ymd\THis');
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

        // Organizer - CN is a property parameter, not part of the value (RFC 5545 §3.8.4.3)
        if (!empty($event['organizer_email'])) {
            $property = 'ORGANIZER';
            if (!empty($event['organizer_name'])) {
                $property .= ';CN="' . self::escapeParamValue($event['organizer_name']) . '"';
            }
            $lines[] = $property . ':MAILTO:' . self::stripControlChars($event['organizer_email']);
        }

        // URL
        $eventUrl = 'https://' . self::DOMAIN . '/events/' . $eventId;
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
        // Note: use real newlines here; escapeValue() converts them to the
        // literal "\n" sequence required by RFC 5545 exactly once.
        if (!empty($event['category'])) {
            $description[] = "\nKategorie: " . ucfirst($event['category']);
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
            $description[] = "\nAnfahrt: " . strip_tags($locationInstructions);
        }

        // Requirements - convert Markdown
        if (!empty($event['requirements'])) {
            $requirements = self::markdownToPlainText($event['requirements']);
            $description[] = "\nVoraussetzungen: " . strip_tags($requirements);
        }

        // Safety notes - convert Markdown
        if (!empty($event['safety_notes'])) {
            $safetyNotes = self::markdownToPlainText($event['safety_notes']);
            $description[] = "\nSicherheitshinweise: " . strip_tags($safetyNotes);
        }

        // Preparation notes - convert Markdown
        if (!empty($event['preparation_notes'])) {
            $prepNotes = self::markdownToPlainText($event['preparation_notes']);
            $description[] = "\nVorbereitung: " . strip_tags($prepNotes);
        }

        // Contact info
        if (!empty($event['organizer_name']) || !empty($event['organizer_email'])) {
            $description[] = "\nKontakt:";
            if (!empty($event['organizer_name'])) {
                $description[] = "Name: " . $event['organizer_name'];
            }
            if (!empty($event['organizer_email'])) {
                $description[] = "E-Mail: " . $event['organizer_email'];
            }
        }

        // Event URL
        $description[] = "\nMehr Informationen: https://" . self::DOMAIN . "/events/" . $event['id'];

        return implode("\n", $description);
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
     * Remove characters that can break out of a content line.
     *
     * RFC 5545 forbids CTLs inside content lines; a raw CR/LF would end the
     * line and let user-supplied text inject arbitrary calendar properties.
     * Used for values that are not TEXT (URIs, parameters) and therefore
     * cannot go through escapeValue().
     */
    private static function stripControlChars(string $value): string
    {
        // Byte-wise on purpose: every UTF-8 continuation byte is >= 0x80, so
        // dropping the C0 range and DEL never damages a multi-byte sequence.
        return preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? '';
    }

    /**
     * Sanitize a property parameter value used inside a quoted string.
     * On top of the CTLs, DQUOTE has to go: it would terminate the quoted
     * value early (RFC 5545 QSAFE-CHAR).
     */
    private static function escapeParamValue(string $value): string
    {
        return str_replace('"', '', self::stripControlChars($value));
    }

    /**
     * Send a generated calendar as a download.
     *
     * An ICS stream has to start with "BEGIN:VCALENDAR", so anything that
     * leaked into the output buffer before us - a deprecation notice, stray
     * whitespace, a BOM - is discarded first. api/index.php opens the buffer
     * before the autoloader runs so even notices raised while loading a class
     * are caught here. No BOM is added either: it breaks strict parsers
     * (Thunderbird, ical.js, Google import); UTF-8 is declared via Content-Type.
     *
     * If output was already flushed the response cannot be repaired, and
     * sending headers anyway would only stack "headers already sent" warnings
     * on top of it - so the content is emitted bare.
     */
    private static function sendCalendarResponse(string $icsContent, string $filename): void
    {
        if (!mb_check_encoding($icsContent, 'UTF-8')) {
            $icsContent = mb_convert_encoding($icsContent, 'UTF-8', 'auto');
        }

        while (ob_get_level() > 0 && ob_end_clean()) {
            // discard whatever was buffered before the calendar
        }

        if (!headers_sent()) {
            header('Content-Type: text/calendar; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . self::sanitizeFilename($filename) . '"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
            header('Content-Length: ' . strlen($icsContent));
        }

        echo $icsContent;
    }

    /**
     * Reduce a filename to characters that are safe inside a quoted
     * Content-Disposition value. PHP's header() already refuses CR/LF, but a
     * DQUOTE would still break out of the filename.
     */
    private static function sanitizeFilename(string $filename): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '-', $filename) ?? '';
        $safe = trim($safe, '-');

        return $safe === '' ? 'calendar.ics' : $safe;
    }

    /**
     * Resolve a stored timezone identifier to one PHP actually knows.
     *
     * Guards the Carbon::parse() calls: an unknown identifier would throw and
     * take the whole feed down, and it must never reach a content line either.
     */
    private static function resolveTimezone(mixed $timezone): string
    {
        if (!is_string($timezone) || $timezone === '') {
            return self::DEFAULT_TIMEZONE;
        }

        try {
            new \DateTimeZone($timezone);
        } catch (\Exception) {
            return self::DEFAULT_TIMEZONE;
        }

        return $timezone;
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


        self::sendCalendarResponse($icsContent, $filename);
    }

    /**
     * Generate single event download with proper HTTP headers
     */
    public static function outputSingleEvent(array $event): void
    {
        $icsContent = self::generateSingleEvent($event);
        $filename = self::sanitizeFilename('event-' . ($event['slug'] ?? $event['id']) . '.ics');


        self::sendCalendarResponse($icsContent, $filename);
    }

    /**
     * Generate ICS content for a recurring event series with RRULE
     */
    public static function generateSeriesEvent(array $series): string
    {
        $events = [self::formatSeriesEvent($series)];
        return self::createICSContent($events);
    }

    /**
     * Format a recurring event series for ICS with RRULE
     *
     * IMPORTANT: The DTSTART must match the first actual occurrence of the RRULE pattern.
     * For example, if RRULE is "every 3rd Monday", DTSTART must be on a Monday.
     */
    private static function formatSeriesEvent(array $series): array
    {
        $timezone = self::resolveTimezone($series['timezone'] ?? null);
        $lines = [];

        // Get start time from series
        $startTime = $series['start_time'] ?? '19:00:00';
        $durationMinutes = $series['default_duration_minutes'] ?? 120;

        // We need to find the first actual occurrence using RRuleProcessor
        // The start_date might not align with the RRULE pattern
        $seriesStartDate = Carbon::parse($series['start_date'], $timezone);

        // Create a pseudo event to expand using RRuleProcessor
        $pseudoEvent = [
            'id' => 'temp',
            'start_datetime' => $seriesStartDate->format('Y-m-d') . ' ' . $startTime,
            'end_datetime' => $seriesStartDate->copy()->addMinutes($durationMinutes)->format('Y-m-d H:i:s'),
            'rrule' => $series['rrule'],
            'timezone' => $timezone,
        ];

        // Expand to find the first occurrence (search up to 1 year ahead)
        $searchEnd = $seriesStartDate->copy()->addYear();
        $instances = RRuleProcessor::expandRecurringEvent($pseudoEvent, $seriesStartDate, $searchEnd, []);

        if (!empty($instances)) {
            // Use the first actual occurrence as DTSTART
            $firstInstance = $instances[0];
            $startDate = Carbon::parse($firstInstance['start_datetime'], $timezone);
            // Calculate end time from start time + duration (don't use instance end_datetime as it may be wrong)
            $endDate = $startDate->copy()->addMinutes($durationMinutes);
        } else {
            // Fallback: use the series start_date directly
            $startDate = $seriesStartDate->copy();
            $timeParts = explode(':', $startTime);
            $startDate->setTime((int)($timeParts[0] ?? 19), (int)($timeParts[1] ?? 0));
            $endDate = $startDate->copy()->addMinutes($durationMinutes);
        }

        // Event start
        $lines[] = 'BEGIN:VEVENT';

        // UID - unique identifier for the series
        $lines[] = 'UID:series-' . self::stripControlChars((string)($series['id'] ?? '')) . '@' . self::DOMAIN;

        // DTSTAMP - creation timestamp
        $lines[] = 'DTSTAMP:' . Carbon::now('UTC')->format('Ymd\THis\Z');

        // Dates and times with RRULE for recurrence
        // Normalised to DEFAULT_TIMEZONE - see formatEvent() for the reasoning
        $lines[] = 'DTSTART;TZID=' . self::DEFAULT_TIMEZONE . ':'
            . $startDate->copy()->setTimezone(self::DEFAULT_TIMEZONE)->format('Ymd\THis');
        $lines[] = 'DTEND;TZID=' . self::DEFAULT_TIMEZONE . ':'
            . $endDate->copy()->setTimezone(self::DEFAULT_TIMEZONE)->format('Ymd\THis');

        // RRULE for recurrence - enhanced for maximum compatibility
        if (!empty($series['rrule'])) {
            // Clean RRULE - remove DTSTART prefix if present.
            // RECUR is a structured value that escapeValue() would corrupt, so
            // CTLs are stripped instead to keep the rule on a single line.
            $rrule = self::stripControlChars((string)$series['rrule']);
            if (str_starts_with($rrule, 'DTSTART')) {
                // Extract just the RRULE part
                if (preg_match('/RRULE:(.+)$/m', $rrule, $matches)) {
                    $rrule = $matches[1];
                }
            }
            // Remove RRULE: prefix if present (we add it ourselves)
            $rrule = preg_replace('/^RRULE:/i', '', $rrule);

            // Enhance RRULE for maximum compatibility
            $rrule = self::enhanceRRuleForCompatibility($rrule, $series);

            $lines[] = 'RRULE:' . $rrule;
        }

        // EXDATE - excluded dates
        if (!empty($series['exdates'])) {
            $exdates = JsonHelper::decodeArray($series['exdates']);
            if (!empty($exdates)) {
                foreach ($exdates as $exdate) {
                    $exdateCarbon = Carbon::parse($exdate, $timezone);
                    // Set same time as DTSTART for proper exclusion
                    $exdateCarbon->setTime($startDate->hour, $startDate->minute);
                    $lines[] = 'EXDATE;TZID=' . self::DEFAULT_TIMEZONE . ':'
                        . $exdateCarbon->setTimezone(self::DEFAULT_TIMEZONE)->format('Ymd\THis');
                }
            }
        }

        // Basic properties
        $lines[] = 'SUMMARY:' . self::escapeValue($series['title']);

        if (!empty($series['description'])) {
            $description = self::markdownToPlainText($series['description']);
            $description = strip_tags($description);

            // Add series URL (real newlines - escapeValue converts them once)
            $description .= "\n\nMehr Informationen: https://" . self::DOMAIN . "/events";

            $lines[] = 'DESCRIPTION:' . self::escapeValue($description);
        }

        // Location
        if (!empty($series['default_location_name']) || !empty($series['default_location_address'])) {
            $location = [];
            if (!empty($series['default_location_name'])) {
                $location[] = $series['default_location_name'];
            }
            if (!empty($series['default_location_address'])) {
                $location[] = $series['default_location_address'];
            }
            $lines[] = 'LOCATION:' . self::escapeValue(implode(', ', $location));
        }

        // Categories/tags
        if (!empty($series['tags'])) {
            $tags = is_string($series['tags']) ? json_decode($series['tags'], true) : $series['tags'];
            if (is_array($tags) && !empty($tags)) {
                $lines[] = 'CATEGORIES:' . implode(',', array_map([self::class, 'escapeValue'], $tags));
            }
        }

        // Status
        $status = match ($series['status'] ?? 'published') {
            'cancelled' => 'CANCELLED',
            'draft' => 'TENTATIVE',
            default => 'CONFIRMED'
        };
        $lines[] = 'STATUS:' . $status;

        // Timestamps
        if (!empty($series['created_at'])) {
            $createdAt = Carbon::parse($series['created_at']);
            $lines[] = 'CREATED:' . $createdAt->utc()->format('Ymd\THis\Z');
        }

        if (!empty($series['updated_at'])) {
            $updatedAt = Carbon::parse($series['updated_at']);
            $lines[] = 'LAST-MODIFIED:' . $updatedAt->utc()->format('Ymd\THis\Z');
        }

        // Sequence number
        $lines[] = 'SEQUENCE:0';

        // Event end
        $lines[] = 'END:VEVENT';

        return $lines;
    }

    /**
     * Enhance RRULE for maximum compatibility across calendar clients
     *
     * Some clients (Thunderbird/Betterbird, older Outlook) need additional
     * parameters to correctly interpret complex recurrence rules.
     */
    private static function enhanceRRuleForCompatibility(string $rrule, array $series): string
    {
        $parts = explode(';', $rrule);
        $hasWkst = false;
        $hasUntil = false;
        $hasCount = false;
        $hasByday = false;

        foreach ($parts as $part) {
            $key = strtoupper(explode('=', $part)[0]);
            if ($key === 'WKST') $hasWkst = true;
            if ($key === 'UNTIL') $hasUntil = true;
            if ($key === 'COUNT') $hasCount = true;
            if ($key === 'BYDAY') $hasByday = true;
        }

        // Add WKST=MO (week start Monday) for BYDAY rules - required by some clients
        if ($hasByday && !$hasWkst) {
            $parts[] = 'WKST=MO';
        }

        // If series has an end_date and RRULE has no UNTIL/COUNT, add UNTIL
        // This helps clients that don't handle infinite recurrences well
        if (!$hasUntil && !$hasCount && !empty($series['end_date'])) {
            $endDate = Carbon::parse($series['end_date'])->endOfDay();
            $parts[] = 'UNTIL=' . $endDate->utc()->format('Ymd\THis\Z');
        }

        return implode(';', $parts);
    }

    /**
     * Output series ICS download with proper HTTP headers
     */
    public static function outputSeriesEvent(array $series): void
    {
        $icsContent = self::generateSeriesEvent($series);
        $filename = self::sanitizeFilename('series-' . ($series['slug'] ?? $series['id']) . '.ics');


        self::sendCalendarResponse($icsContent, $filename);
    }
}
