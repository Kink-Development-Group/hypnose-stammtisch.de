<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Models\Event;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\JsonHelper;
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
     * Reduce the token from the URL to either a real token or null.
     *
     * Both callers pass null for the public feed (no path segment, no ?token),
     * so the null has to be absorbed before it reaches a string function - with
     * strict_types that is a TypeError, not a coercion.
     */
    private static function normalizeFeedToken(?string $token): ?string
    {
        $normalized = trim($token ?? '');

        if ($normalized === '' || strcasecmp($normalized, 'public') === 0) {
            return null;
        }

        return $normalized;
    }

    /**
     * Generate ICS calendar feed
     * GET /api/calendar/feed
     * GET /api/calendar/feed/{token}
     */
    public function feed(?string $token = null): void
    {
        try {
            $normalizedToken = self::normalizeFeedToken($token);

            // Validate token if provided
            if ($normalizedToken && !$this->validateFeedToken($normalizedToken)) {
                Response::json(['success' => false, 'error' => 'Invalid calendar feed token'], 403);
                return;
            }

            // Get events with expansion for recurring events
            $events = $this->getExpandedEventsForFeed();

            // Generate ICS content using the new ICSGenerator
            $filename = $normalizedToken ? 'private-calendar.ics' : 'public-calendar.ics';
            ICSGenerator::outputCalendarFeed($events, $filename);

            // Update token access tracking
            if ($normalizedToken) {
                $this->updateTokenAccess($normalizedToken);
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

            // Same generator as the feed - it escapes per RFC 5545, keeps the
            // TZID resolvable and discards anything buffered before the stream.
            ICSGenerator::outputSingleEvent($this->eventToArray($event));
        } catch (\Exception $e) {
            error_log("Event ICS error: " . $e->getMessage());
            Response::error('Failed to generate event ICS', 500);
        }
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
                        JsonHelper::decodeArray($eventArray['exdates'] ?? '[]')
                    );

                    if (!empty($instances)) {
                        $expandedEvents = array_merge($expandedEvents, $instances);
                    } else {
                        // If expansion returned no instances (e.g., all dates in past),
                        // add base event if it falls within range
                        $eventStart = Carbon::parse($eventArray['start_datetime']);
                        if ($eventStart->between($startDate, $endDate)) {
                            $expandedEvents[] = $eventArray;
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Error expanding recurring event {$eventArray['id']}: " . $e->getMessage());
                    // Add the base event if expansion fails
                    $expandedEvents[] = $eventArray;
                }
            } else {
                // Add single event (also handles is_recurring=true with empty rrule)
                $eventStart = Carbon::parse($eventArray['start_datetime']);
                if ($eventStart->between($startDate, $endDate)) {
                    $expandedEvents[] = $eventArray;
                }
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
