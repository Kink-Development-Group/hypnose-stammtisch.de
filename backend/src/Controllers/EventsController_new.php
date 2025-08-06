<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Models\Event;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\Validator;
use HypnoseStammtisch\Utils\MockData;

/**
 * Events API Controller
 */
class EventsController
{
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

    /**
     * Get all events with optional filters
     * GET /api/events
     */
    public function index(): void
    {
        try {
            $filters = $this->getFilters();
            $events = Event::getAllPublished($filters);

            $eventsData = array_map(fn($event) => $this->eventToArray($event), $events);

            Response::json([
                'success' => true,
                'data' => $eventsData,
                'meta' => [
                    'count' => count($eventsData),
                    'filters' => $filters
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Error fetching events: " . $e->getMessage());
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch events'
            ], 500);
        }
    }

    /**
     * Get upcoming events
     * GET /api/events/upcoming
     */
    public function upcoming(): void
    {
        try {
            $limit = (int)($_GET['limit'] ?? 5);
            $limit = min(max($limit, 1), 20); // Between 1 and 20

            $events = Event::getUpcoming($limit);
            $eventsData = array_map(fn($event) => $this->eventToArray($event), $events);

            Response::json([
                'success' => true,
                'data' => $eventsData,
                'meta' => [
                    'count' => count($eventsData),
                    'limit' => $limit
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Error fetching upcoming events: " . $e->getMessage());
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch upcoming events'
            ], 500);
        }
    }

    /**
     * Get featured events
     * GET /api/events/featured
     */
    public function featured(): void
    {
        try {
            $limit = (int)($_GET['limit'] ?? 3);
            $limit = min(max($limit, 1), 10); // Between 1 and 10

            $events = Event::getFeatured($limit);
            $eventsData = array_map(fn($event) => $this->eventToArray($event), $events);

            Response::json([
                'success' => true,
                'data' => $eventsData,
                'meta' => [
                    'count' => count($eventsData),
                    'limit' => $limit
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Error fetching featured events: " . $e->getMessage());
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch featured events'
            ], 500);
        }
    }

    /**
     * Get event metadata (categories, tags, etc.)
     * GET /api/events/meta
     */
    public function meta(): void
    {
        try {
            Response::json([
                'success' => true,
                'data' => [
                    'categories' => MockData::getCategories(),
                    'tags' => MockData::getTags(),
                    'filters' => [
                        'category' => 'Filter by event category',
                        'tag' => 'Search by tag',
                        'location' => 'Search in location/address',
                        'from_date' => 'Events from this date (YYYY-MM-DD)',
                        'to_date' => 'Events until this date (YYYY-MM-DD)',
                        'limit' => 'Maximum number of results (1-100)',
                        'page' => 'Page number for pagination'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Error fetching meta data: " . $e->getMessage());
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch meta data'
            ], 500);
        }
    }

    /**
     * Get single event by ID or slug
     * GET /api/events/{id}
     */
    public function show(string $identifier): void
    {
        try {
            // Try mock data first since database is not set up
            $mockEvent = MockData::getEventByIdOrSlug($identifier);
            if ($mockEvent) {
                Response::json([
                    'success' => true,
                    'data' => $mockEvent
                ]);
                return;
            }

            Response::json([
                'success' => false,
                'error' => 'Event not found'
            ], 404);

        } catch (\Exception $e) {
            error_log("Error fetching event: " . $e->getMessage());
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch event'
            ], 500);
        }
    }

    /**
     * Store new event (future admin feature)
     * POST /api/events
     */
    public function store(): void
    {
        Response::json([
            'success' => false,
            'error' => 'Event creation not yet implemented'
        ], 501);
    }

    /**
     * Update event (future admin feature)
     * PUT /api/events/{id}
     */
    public function update(int $id): void
    {
        Response::json([
            'success' => false,
            'error' => 'Event updates not yet implemented'
        ], 501);
    }

    /**
     * Delete event (future admin feature)
     * DELETE /api/events/{id}
     */
    public function destroy(int $id): void
    {
        Response::json([
            'success' => false,
            'error' => 'Event deletion not yet implemented'
        ], 501);
    }

    /**
     * Get query filters from request
     */
    private function getFilters(): array
    {
        return [
            'category' => $_GET['category'] ?? null,
            'difficulty_level' => $_GET['difficulty_level'] ?? null,
            'location_type' => $_GET['location_type'] ?? null,
            'from_date' => $_GET['from_date'] ?? null,
            'to_date' => $_GET['to_date'] ?? null,
            'limit' => isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : null,
            'featured' => isset($_GET['featured']) ? filter_var($_GET['featured'], FILTER_VALIDATE_BOOLEAN) : null
        ];
    }
}
