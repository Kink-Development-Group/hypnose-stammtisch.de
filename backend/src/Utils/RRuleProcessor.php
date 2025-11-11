<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use Carbon\Carbon;
use Exception;

/**
 * RRULE processing and expansion utility
 * Handles RFC 5545 compliant RRULE parsing and event expansion
 */
class RRuleProcessor
{
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
        $byDay = isset($rrule['BYDAY']) ? explode(',', $rrule['BYDAY']) : null;

        $current = $eventStart->copy();
        $instanceCount = 0;
        $maxInstances = $count ?? 1000; // Safety limit

        while ($current->lte($endDate) && $instanceCount < $maxInstances) {
            if ($current->gte($startDate)) {
                // Check if this date is not in exdates
                $isExcluded = false;
                foreach ($exdateObjects as $exdate) {
                    if ($current->isSameDay($exdate)) {
                        $isExcluded = true;
                        break;
                    }
                }

                if (!$isExcluded) {
                    $instanceEnd = $current->copy()->addMinutes($duration);

                    $instance = $event;
                    $instance['id'] = $event['id'] . '_' . $current->format('Y-m-d');
                    $instance['start_datetime'] = $current->toDateTimeString();
                    $instance['end_datetime'] = $instanceEnd->toDateTimeString();
                    $instance['is_recurring_instance'] = true;
                    $instance['parent_event_id'] = $event['id'];

                    $instances[] = $instance;
                }
            }

            // Calculate next occurrence
            switch ($freq) {
                case 'DAILY':
                    $current->addDays($interval);
                    break;

                case 'WEEKLY':
                    if ($byDay) {
                        $current = self::getNextByDayOccurrence($current, $byDay, $interval);
                    } else {
                        $current->addWeeks($interval);
                    }
                    break;

                case 'MONTHLY':
                    $current->addMonths($interval);
                    break;

                case 'YEARLY':
                    $current->addYears($interval);
                    break;

                default:
                    throw new Exception("Unsupported FREQ: {$freq}");
            }

            $instanceCount++;

            // Check UNTIL condition
            if ($until && $current->gt($until)) {
                break;
            }

            // Check COUNT condition
            if ($count && $instanceCount >= $count) {
                break;
            }
        }

        return $instances;
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
     * Get next occurrence based on BYDAY rule
     */
    private static function getNextByDayOccurrence(Carbon $current, array $byDay, int $interval): Carbon
    {
        $dayMap = [
            'SU' => 0,
            'MO' => 1,
            'TU' => 2,
            'WE' => 3,
            'TH' => 4,
            'FR' => 5,
            'SA' => 6
        ];

        $targetDays = array_map(function ($day) use ($dayMap) {
            return $dayMap[strtoupper($day)] ?? null;
        }, $byDay);

        $targetDays = array_filter($targetDays, function ($day) {
            return $day !== null;
        });

        if (empty($targetDays)) {
            return $current->addWeeks($interval);
        }

        // Find next occurrence
        $next = $current->copy()->addDay();
        $weekIncrement = 0;

        while ($weekIncrement < $interval || !in_array($next->dayOfWeek, $targetDays)) {
            if ($next->dayOfWeek === 0) { // Sunday, new week
                $weekIncrement++;
            }

            if ($weekIncrement >= $interval && in_array($next->dayOfWeek, $targetDays)) {
                break;
            }

            $next->addDay();

            // Safety check to prevent infinite loop
            if ($next->diffInDays($current) > 365) {
                return $current->addWeeks($interval);
            }
        }

        // Set time to match original event
        $next->setTime($current->hour, $current->minute, $current->second);

        return $next;
    }

    /**
     * Validate RRULE string
     */
    public static function validateRRule(string $rrule): array
    {
        $errors = [];
        $components = self::parseRRule($rrule);

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
            $byDays = explode(',', $components['BYDAY']);

            foreach ($byDays as $day) {
                if (!in_array(strtoupper($day), $validDays)) {
                    $errors[] = 'Invalid BYDAY value: ' . $day;
                }
            }
        }

        return $errors;
    }

    /**
     * Generate RRULE string from components
     */
    public static function buildRRule(array $components): string
    {
        $parts = [];

        foreach ($components as $key => $value) {
            if (!empty($value)) {
                $parts[] = strtoupper($key) . '=' . $value;
            }
        }

        return implode(';', $parts);
    }
}
