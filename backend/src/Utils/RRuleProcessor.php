<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use Carbon\Carbon;
use Exception;

/**
 * RRULE processing and expansion utility
 * Handles RFC 5545 compliant RRULE parsing and event expansion
 *
 * Supports:
 * - FREQ: DAILY, WEEKLY, MONTHLY, YEARLY
 * - INTERVAL: Repeat every N periods
 * - COUNT: Number of occurrences
 * - UNTIL: End date for recurrence
 * - BYDAY: Days of the week (MO, TU, WE, TH, FR, SA, SU)
 * - BYMONTHDAY: Days of the month (1-31)
 * - BYSETPOS: Position in month (1-4, -1 for last)
 */
class RRuleProcessor
{
    private const DAY_MAP = [
        'SU' => 0,
        'MO' => 1,
        'TU' => 2,
        'WE' => 3,
        'TH' => 4,
        'FR' => 5,
        'SA' => 6
    ];

    private const REVERSE_DAY_MAP = [
        0 => 'SU',
        1 => 'MO',
        2 => 'TU',
        3 => 'WE',
        4 => 'TH',
        5 => 'FR',
        6 => 'SA'
    ];

    /**
     * Parse RRULE string into components
     */
    public static function parseRRule(string $rrule): array
    {
        $components = [];
        $parts = explode(';', $rrule);

        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                [$key, $value] = explode('=', $part, 2);
                $components[strtoupper($key)] = $value;
            }
        }

        return $components;
    }

    /**
     * Parse BYDAY value which can include position prefixes
     * Examples: "MO", "TU,TH", "1MO" (first Monday), "-1FR" (last Friday), "2TU" (second Tuesday)
     *
     * @return array{days: array, positions: array} Days and their positions
     */
    public static function parseByDay(string $byDay): array
    {
        $days = [];
        $positions = [];

        $parts = explode(',', $byDay);
        foreach ($parts as $part) {
            $part = trim(strtoupper($part));
            if (empty($part)) {
                continue;
            }

            // Check for position prefix (e.g., "1MO", "-1FR", "2TU")
            if (preg_match('/^(-?\d+)?([A-Z]{2})$/', $part, $matches)) {
                $position = !empty($matches[1]) ? (int)$matches[1] : null;
                $day = $matches[2];

                if (isset(self::DAY_MAP[$day])) {
                    $days[] = $day;
                    $positions[$day] = $position;
                }
            }
        }

        return ['days' => $days, 'positions' => $positions];
    }

    /**
     * Expand recurring event into individual instances
     *
     * @param array $event Event data with RRULE
     * @param Carbon $startDate Expansion start date
     * @param Carbon $endDate Expansion end date
     * @param array $exdates Array of exception dates (ISO format)
     * @return array Array of expanded event instances
     */
    public static function expandRecurringEvent(
        array $event,
        Carbon $startDate,
        Carbon $endDate,
        array $exdates = []
    ): array {
        if (empty($event['rrule'])) {
            return [];
        }
        // Normalisiere RRULE: entferne evtl. enthaltene DTSTART-Zeile und 'RRULE:' Präfix
        $normalizedRRule = self::normalizeRRuleString($event['rrule']);
        $rrule = self::parseRRule($normalizedRRule);
        $instances = [];

        $eventStart = Carbon::parse($event['start_datetime'], $event['timezone'] ?? 'Europe/Berlin');
        $eventEnd = Carbon::parse($event['end_datetime'], $event['timezone'] ?? 'Europe/Berlin');
        $duration = $eventEnd->diffInMinutes($eventStart);

        // Convert exdates to Carbon objects for comparison
        $exdateObjects = array_map(function ($date) use ($event) {
            return Carbon::parse($date, $event['timezone'] ?? 'Europe/Berlin');
        }, $exdates);

        $freq = $rrule['FREQ'] ?? 'WEEKLY';
        $interval = (int)($rrule['INTERVAL'] ?? 1);
        $count = isset($rrule['COUNT']) ? (int)$rrule['COUNT'] : null;
        $until = isset($rrule['UNTIL']) ? Carbon::parse($rrule['UNTIL']) : null;

        // Parse BYDAY with position support
        $byDayData = isset($rrule['BYDAY']) ? self::parseByDay($rrule['BYDAY']) : null;
        $byDay = $byDayData['days'] ?? null;
        $byDayPositions = $byDayData['positions'] ?? [];

        // Parse BYSETPOS (position in month, e.g., 1 = first, -1 = last)
        $bySetPos = isset($rrule['BYSETPOS']) ? (int)$rrule['BYSETPOS'] : null;

        // Parse BYMONTHDAY
        $byMonthDay = isset($rrule['BYMONTHDAY']) ? array_map('intval', explode(',', $rrule['BYMONTHDAY'])) : null;

        // For monthly/yearly with BYSETPOS, we need to start iteration from the beginning of the month
        // to correctly find the n-th weekday
        $current = $eventStart->copy();
        if ($freq === 'MONTHLY' && ($bySetPos !== null || !empty($byDayPositions))) {
            $current->startOfMonth();
        }

        $instanceCount = 0;
        $maxInstances = $count ?? 1000; // Safety limit
        $generatedCount = 0; // Count of actually generated instances

        while ($current->lte($endDate) && $generatedCount < $maxInstances) {
            $occurrenceDates = [];

            // Determine occurrence dates based on frequency and rules
            switch ($freq) {
                case 'DAILY':
                    $occurrenceDates[] = $current->copy();
                    break;

                case 'WEEKLY':
                    if ($byDay) {
                        // Get occurrences for specified weekdays in this week
                        $occurrenceDates = self::getWeeklyOccurrences($current, $byDay, $eventStart);
                    } else {
                        $occurrenceDates[] = $current->copy();
                    }
                    break;

                case 'MONTHLY':
                    if ($bySetPos !== null && $byDay) {
                        // N-th weekday of month (e.g., first Tuesday, last Friday)
                        $occurrenceDates = self::getMonthlyBySetPosOccurrences($current, $bySetPos, $byDay, $eventStart);
                    } elseif ($byDay && !empty($byDayPositions)) {
                        // BYDAY with position prefix (e.g., 1MO, 2TU)
                        $occurrenceDates = self::getMonthlyByDayWithPosOccurrences($current, $byDay, $byDayPositions, $eventStart);
                    } elseif ($byMonthDay) {
                        // Specific day(s) of month
                        $occurrenceDates = self::getMonthlyByMonthDayOccurrences($current, $byMonthDay, $eventStart);
                    } else {
                        // Default: same day of month as start
                        $occurrenceDates[] = $current->copy();
                    }
                    break;

                case 'YEARLY':
                    $occurrenceDates[] = $current->copy();
                    break;

                default:
                    throw new Exception("Unsupported FREQ: {$freq}");
            }

            // Process each occurrence date
            foreach ($occurrenceDates as $occDate) {
                if ($generatedCount >= $maxInstances) {
                    break;
                }

                // Check UNTIL condition
                if ($until && $occDate->gt($until)) {
                    continue;
                }

                // Check if within requested range
                if ($occDate->lt($startDate) || $occDate->gt($endDate)) {
                    continue;
                }

                // Check if this date is excluded
                $isExcluded = false;
                foreach ($exdateObjects as $exdate) {
                    if ($occDate->isSameDay($exdate)) {
                        $isExcluded = true;
                        break;
                    }
                }

                if (!$isExcluded) {
                    $instanceEnd = $occDate->copy()->addMinutes($duration);

                    $instance = $event;
                    $instance['id'] = $event['id'] . '_' . $occDate->format('Y-m-d');
                    $instance['start_datetime'] = $occDate->toDateTimeString();
                    $instance['end_datetime'] = $instanceEnd->toDateTimeString();
                    $instance['is_recurring_instance'] = true;
                    $instance['parent_event_id'] = $event['id'];
                    $instance['instance_date'] = $occDate->format('Y-m-d');

                    $instances[] = $instance;
                    $generatedCount++;
                }
            }

            $instanceCount++;

            // Move to next period
            switch ($freq) {
                case 'DAILY':
                    $current->addDays($interval);
                    break;
                case 'WEEKLY':
                    $current->addWeeks($interval);
                    // Reset to start of week for consistent iteration
                    $current->startOfWeek(Carbon::MONDAY);
                    break;
                case 'MONTHLY':
                    $current->addMonths($interval);
                    $current->startOfMonth();
                    break;
                case 'YEARLY':
                    $current->addYears($interval);
                    break;
            }

            // Safety check for infinite loop prevention
            if ($instanceCount > 10000) {
                error_log("RRuleProcessor: Safety limit reached for event {$event['id']}");
                break;
            }
        }

        return $instances;
    }

    /**
     * Get weekly occurrences for specified weekdays
     */
    private static function getWeeklyOccurrences(Carbon $weekStart, array $byDay, Carbon $eventStart): array
    {
        $occurrences = [];
        $startOfWeek = $weekStart->copy()->startOfWeek(Carbon::MONDAY);

        foreach ($byDay as $day) {
            $dayNum = self::DAY_MAP[$day] ?? null;
            if ($dayNum === null) {
                continue;
            }

            // Carbon uses 0=Sunday, 1=Monday, etc.
            $occurrence = $startOfWeek->copy();
            $daysToAdd = $dayNum === 0 ? 6 : $dayNum - 1; // Adjust for Monday start
            $occurrence->addDays($daysToAdd);

            // Set time from original event
            $occurrence->setTime($eventStart->hour, $eventStart->minute, $eventStart->second);

            $occurrences[] = $occurrence;
        }

        return $occurrences;
    }

    /**
     * Get monthly occurrences using BYSETPOS (n-th weekday of month)
     * Example: BYSETPOS=1;BYDAY=TU = first Tuesday of month
     *          BYSETPOS=-1;BYDAY=FR = last Friday of month
     */
    private static function getMonthlyBySetPosOccurrences(Carbon $monthStart, int $setPos, array $byDay, Carbon $eventStart): array
    {
        $occurrences = [];
        $month = $monthStart->copy()->startOfMonth();

        foreach ($byDay as $day) {
            $dayNum = self::DAY_MAP[$day] ?? null;
            if ($dayNum === null) {
                continue;
            }

            $occurrence = self::getNthWeekdayOfMonth($month, $dayNum, $setPos);
            if ($occurrence) {
                $occurrence->setTime($eventStart->hour, $eventStart->minute, $eventStart->second);
                $occurrences[] = $occurrence;
            }
        }

        return $occurrences;
    }

    /**
     * Get monthly occurrences using BYDAY with position prefix
     * Example: BYDAY=1MO = first Monday, BYDAY=2TU = second Tuesday
     */
    private static function getMonthlyByDayWithPosOccurrences(Carbon $monthStart, array $byDay, array $positions, Carbon $eventStart): array
    {
        $occurrences = [];
        $month = $monthStart->copy()->startOfMonth();

        foreach ($byDay as $day) {
            $pos = $positions[$day] ?? 1;
            $dayNum = self::DAY_MAP[$day] ?? null;
            if ($dayNum === null) {
                continue;
            }

            $occurrence = self::getNthWeekdayOfMonth($month, $dayNum, $pos);
            if ($occurrence) {
                $occurrence->setTime($eventStart->hour, $eventStart->minute, $eventStart->second);
                $occurrences[] = $occurrence;
            }
        }

        return $occurrences;
    }

    /**
     * Get monthly occurrences for specific days of month
     */
    private static function getMonthlyByMonthDayOccurrences(Carbon $monthStart, array $days, Carbon $eventStart): array
    {
        $occurrences = [];
        $month = $monthStart->copy()->startOfMonth();
        $daysInMonth = $month->daysInMonth;

        foreach ($days as $day) {
            if ($day < 1 || $day > $daysInMonth) {
                continue;
            }

            $occurrence = $month->copy()->day($day);
            $occurrence->setTime($eventStart->hour, $eventStart->minute, $eventStart->second);
            $occurrences[] = $occurrence;
        }

        return $occurrences;
    }

    /**
     * Get the n-th occurrence of a weekday in a month
     *
     * @param Carbon $month The month to search in
     * @param int $weekday Day of week (0=Sunday, 1=Monday, etc.)
     * @param int $position Position (1=first, 2=second, ..., -1=last)
     * @return Carbon|null The date or null if not found
     */
    private static function getNthWeekdayOfMonth(Carbon $month, int $weekday, int $position): ?Carbon
    {
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        if ($position > 0) {
            // Find n-th occurrence from start of month
            $current = $startOfMonth->copy();

            // Find first occurrence of the weekday
            while ($current->dayOfWeek !== $weekday && $current->lte($endOfMonth)) {
                $current->addDay();
            }

            if ($current->gt($endOfMonth)) {
                return null;
            }

            // Move to n-th occurrence
            $current->addWeeks($position - 1);

            // Check if still in same month
            if ($current->month !== $startOfMonth->month) {
                return null;
            }

            return $current;
        } else {
            // Find n-th occurrence from end of month (-1 = last, -2 = second to last)
            $current = $endOfMonth->copy();

            // Find last occurrence of the weekday
            while ($current->dayOfWeek !== $weekday && $current->gte($startOfMonth)) {
                $current->subDay();
            }

            if ($current->lt($startOfMonth)) {
                return null;
            }

            // Move backwards for -2, -3, etc.
            if ($position < -1) {
                $current->subWeeks(abs($position) - 1);
            }

            // Check if still in same month
            if ($current->month !== $startOfMonth->month) {
                return null;
            }

            return $current;
        }
    }

    /**
     * Normalize raw RRULE content which might include full iCal block lines like:
     *   DTSTART:20250812T070000Z\nRRULE:FREQ=DAILY;INTERVAL=1;UNTIL=20250816T215959Z
     * We only need the portion after RRULE: and without line breaks.
     */
    private static function normalizeRRuleString(string $raw): string
    {
        $raw = trim($raw);
        // Split into lines to remove DTSTART and pick RRULE line if present
        $lines = preg_split('/\r?\n/', $raw);
        $candidate = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (stripos($line, 'DTSTART:') === 0) {
                // ignore DTSTART line
                continue;
            }
            if (stripos($line, 'RRULE:') === 0) {
                $line = substr($line, 6); // remove 'RRULE:' prefix
            }
            $candidate[] = $line;
        }
        if (!empty($candidate)) {
            // Wenn mehrere Zeilen übrig sind, verbinde mit ';'
            $raw = implode(';', $candidate);
        }
        // Falls dennoch ein RRULE: Fragment vorhanden, erneut entfernen
        if (stripos($raw, 'RRULE:') === 0) {
            $raw = substr($raw, 6);
        }
        return trim($raw);
    }

    /**
     * Validate RRULE string
     */
    public static function validateRRule(string $rrule): array
    {
        $errors = [];
        $normalizedRRule = self::normalizeRRuleString($rrule);
        $components = self::parseRRule($normalizedRRule);

        // Required FREQ
        if (!isset($components['FREQ'])) {
            $errors[] = 'FREQ is required';
        } elseif (!in_array($components['FREQ'], ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'])) {
            $errors[] = 'Invalid FREQ value';
        }

        // Validate INTERVAL
        if (isset($components['INTERVAL'])) {
            $interval = (int)$components['INTERVAL'];
            if ($interval < 1 || $interval > 366) {
                $errors[] = 'INTERVAL must be between 1 and 366';
            }
        }

        // Validate COUNT
        if (isset($components['COUNT'])) {
            $count = (int)$components['COUNT'];
            if ($count < 1 || $count > 1000) {
                $errors[] = 'COUNT must be between 1 and 1000';
            }
        }

        // Validate UNTIL
        if (isset($components['UNTIL'])) {
            try {
                Carbon::parse($components['UNTIL']);
            } catch (Exception $e) {
                $errors[] = 'Invalid UNTIL date format';
            }
        }

        // Validate BYDAY
        if (isset($components['BYDAY'])) {
            $validDays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
            $byDayData = self::parseByDay($components['BYDAY']);

            foreach ($byDayData['days'] as $day) {
                if (!in_array(strtoupper($day), $validDays)) {
                    $errors[] = 'Invalid BYDAY value: ' . $day;
                }
            }

            // Validate positions if present
            foreach ($byDayData['positions'] as $day => $pos) {
                if ($pos !== null && ($pos < -5 || $pos > 5 || $pos === 0)) {
                    $errors[] = 'Invalid position for ' . $day . ': must be between -5 and 5 (excluding 0)';
                }
            }
        }

        // Validate BYSETPOS
        if (isset($components['BYSETPOS'])) {
            $setPos = (int)$components['BYSETPOS'];
            if ($setPos < -5 || $setPos > 5 || $setPos === 0) {
                $errors[] = 'BYSETPOS must be between -5 and 5 (excluding 0)';
            }
        }

        // Validate BYMONTHDAY
        if (isset($components['BYMONTHDAY'])) {
            $days = explode(',', $components['BYMONTHDAY']);
            foreach ($days as $day) {
                $dayNum = (int)$day;
                if ($dayNum < 1 || $dayNum > 31) {
                    $errors[] = 'BYMONTHDAY values must be between 1 and 31';
                    break;
                }
            }
        }

        // Check for conflicting rules
        if (isset($components['BYSETPOS']) && isset($components['BYMONTHDAY'])) {
            $errors[] = 'BYSETPOS and BYMONTHDAY cannot be used together';
        }

        if (isset($components['COUNT']) && isset($components['UNTIL'])) {
            // Note: RFC 5545 discourages this but doesn't forbid it
            // We'll allow it but warn
            error_log('RRuleProcessor Warning: RRULE contains both COUNT and UNTIL. RFC 5545 discourages this.');
        }

        return $errors;
    }

    /**
     * Generate RRULE string from components
     */
    public static function buildRRule(array $components): string
    {
        $parts = [];

        // Ensure FREQ comes first (standard convention)
        if (isset($components['FREQ'])) {
            $parts[] = 'FREQ=' . strtoupper($components['FREQ']);
            unset($components['FREQ']);
        }

        foreach ($components as $key => $value) {
            if ($value !== null && $value !== '') {
                $parts[] = strtoupper($key) . '=' . $value;
            }
        }

        return implode(';', $parts);
    }

    /**
     * Generate human-readable description of RRULE (German)
     */
    public static function describeRRule(string $rrule): string
    {
        $normalizedRRule = self::normalizeRRuleString($rrule);
        $components = self::parseRRule($normalizedRRule);

        $parts = [];
        $freq = $components['FREQ'] ?? 'WEEKLY';
        $interval = (int)($components['INTERVAL'] ?? 1);

        // Frequency description
        $freqMap = [
            'DAILY' => $interval === 1 ? 'Täglich' : "Alle {$interval} Tage",
            'WEEKLY' => $interval === 1 ? 'Wöchentlich' : "Alle {$interval} Wochen",
            'MONTHLY' => $interval === 1 ? 'Monatlich' : "Alle {$interval} Monate",
            'YEARLY' => $interval === 1 ? 'Jährlich' : "Alle {$interval} Jahre",
        ];
        $parts[] = $freqMap[$freq] ?? $freq;

        // Day names in German
        $dayNames = [
            'MO' => 'Montag',
            'TU' => 'Dienstag',
            'WE' => 'Mittwoch',
            'TH' => 'Donnerstag',
            'FR' => 'Freitag',
            'SA' => 'Samstag',
            'SU' => 'Sonntag'
        ];

        // Position names in German
        $posNames = [
            1 => 'ersten',
            2 => 'zweiten',
            3 => 'dritten',
            4 => 'vierten',
            5 => 'fünften',
            -1 => 'letzten',
            -2 => 'vorletzten'
        ];

        // BYSETPOS + BYDAY (n-th weekday of month)
        if (isset($components['BYSETPOS']) && isset($components['BYDAY'])) {
            $setPos = (int)$components['BYSETPOS'];
            $byDayData = self::parseByDay($components['BYDAY']);
            $dayName = $dayNames[$byDayData['days'][0] ?? 'MO'] ?? $components['BYDAY'];
            $posName = $posNames[$setPos] ?? "{$setPos}.";
            $parts[] = "am {$posName} {$dayName}";
        }
        // BYDAY with positions (e.g., 1MO, 2TU)
        elseif (isset($components['BYDAY'])) {
            $byDayData = self::parseByDay($components['BYDAY']);
            $dayDescriptions = [];

            foreach ($byDayData['days'] as $day) {
                $pos = $byDayData['positions'][$day] ?? null;
                $dayName = $dayNames[$day] ?? $day;

                if ($pos !== null) {
                    $posName = $posNames[$pos] ?? "{$pos}.";
                    $dayDescriptions[] = "{$posName} {$dayName}";
                } else {
                    $dayDescriptions[] = $dayName;
                }
            }

            if (!empty($dayDescriptions)) {
                $parts[] = '(' . implode(', ', $dayDescriptions) . ')';
            }
        }

        // BYMONTHDAY
        if (isset($components['BYMONTHDAY'])) {
            $days = explode(',', $components['BYMONTHDAY']);
            $parts[] = 'am ' . implode('./', $days) . '. Tag';
        }

        // COUNT or UNTIL
        if (isset($components['COUNT'])) {
            $parts[] = "für {$components['COUNT']} Termine";
        }
        if (isset($components['UNTIL'])) {
            try {
                $until = Carbon::parse($components['UNTIL']);
                $parts[] = 'bis ' . $until->format('d.m.Y');
            } catch (Exception $e) {
                // Ignore parse errors
            }
        }

        return implode(' ', $parts);
    }
}
