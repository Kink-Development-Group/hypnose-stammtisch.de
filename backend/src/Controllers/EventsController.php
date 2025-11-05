<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Models\Event;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\Validator;
use HypnoseStammtisch\Utils\MockData;
use HypnoseStammtisch\Utils\RRuleProcessor;
use HypnoseStammtisch\Utils\ICSGenerator;
use Carbon\Carbon;

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
      // Bereits ein Array -> normalisieren (z.B. DB-Row oder bereits konvertiert)
        if (is_array($event)) {
            return $this->normalizeEventArray($event);
        }
      // Objekt mit toArray Methode
        if (is_object($event) && method_exists($event, 'toArray')) {
            $arr = $event->toArray();
            return is_array($arr) ? $this->normalizeEventArray($arr) : (array)$arr;
        }
      // Objekt hat evtl. Property oder Closure toArray
        if (is_object($event) && isset($event->toArray) && is_callable($event->toArray)) {
            $arr = call_user_func($event->toArray);
            return is_array($arr) ? $this->normalizeEventArray($arr) : (array)$arr;
        }
      // Generischer Fallback
        return $this->normalizeEventArray((array)$event);
    }

  /**
   * Normalisiere Event-Array Keys (snake_case Erwartung der API) und setze Defaults
   */
    private function normalizeEventArray(array $e): array
    {
      // Falls Modell-Properties im camelCase kommen, mappe auf snake_case
        $map = [
        'startDatetime' => 'start_datetime',
        'endDatetime' => 'end_datetime',
        'isRecurring' => 'is_recurring',
        'recurrenceEndDate' => 'recurrence_end_date',
        'parentEventId' => 'parent_event_id',
        'difficultyLevel' => 'difficulty_level',
        'maxParticipants' => 'max_participants',
        'currentParticipants' => 'current_participants',
        'ageRestriction' => 'age_restriction',
        'isFeatured' => 'is_featured',
        'requiresRegistration' => 'requires_registration',
        'registrationDeadline' => 'registration_deadline',
        'organizerName' => 'organizer_name',
        'organizerEmail' => 'organizer_email',
        'organizerBio' => 'organizer_bio',
        'metaDescription' => 'meta_description',
        'imageUrl' => 'image_url',
        'createdAt' => 'created_at',
        'updatedAt' => 'updated_at'
        ];
        foreach ($map as $from => $to) {
            if (array_key_exists($from, $e) && !array_key_exists($to, $e)) {
                $e[$to] = $e[$from];
            }
        }
      // Minimal erforderliche Felder absichern
        $defaults = [
        'title' => '',
        'slug' => '',
        'start_datetime' => $e['start_datetime'] ?? ($e['start_date'] ?? null),
        'end_datetime' => $e['end_datetime'] ?? ($e['start_datetime'] ?? null),
        'timezone' => $e['timezone'] ?? 'Europe/Berlin'
        ];
        foreach ($defaults as $k => $v) {
            if (!array_key_exists($k, $e)) {
                $e[$k] = $v;
            }
        }
        return $e;
    }

  /**
   * Get all events with optional filters
   * GET /api/events
   */
    public function index(): void
    {
        try {
            $filters = $this->getFilters();
            $expandRecurring = isset($_GET['view']) && $_GET['view'] === 'expanded';

            if ($expandRecurring) {
                // Get expanded events with recurring instances
                $events = $this->getExpandedEvents($filters);
            } else {
              // Get only base events
                $events = Event::getAllPublished($filters);
            }

            $eventsData = array_map(fn($event) => $this->eventToArray($event), $events);

            Response::json([
            'success' => true,
            'data' => $eventsData,
            'meta' => [
            'count' => count($eventsData),
            'filters' => $filters,
            'expanded' => $expandRecurring
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
    public function update(string $id): void
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
    public function destroy(string $id): void
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

  /**
   * Get expanded events including recurring instances
   */
    private function getExpandedEvents(array $filters): array
    {
      // Date range
        $startDate = isset($filters['from_date']) ? Carbon::parse($filters['from_date']) : Carbon::now();
        $endDate = isset($filters['to_date']) ? Carbon::parse($filters['to_date']) : Carbon::now()->addMonths(3);

        $expanded = [];

      // 1. Expand legacy recurring events (events table with rrule)
        $baseEvents = Event::getAllPublished($filters);
        foreach ($baseEvents as $event) {
            $eventArray = $this->eventToArray($event);
            if (!empty($eventArray['is_recurring']) && !empty($eventArray['rrule'])) {
                try {
                    $exdatesRaw = $eventArray['exdates'] ?? '[]';
                    $exdatesDecoded = json_decode($exdatesRaw, true);
                    if ($exdatesDecoded === null && !empty($exdatesRaw)) {
                        error_log("Invalid exdates JSON for event {$eventArray['id']}: " . $exdatesRaw);
                        $exdatesDecoded = [];
                    }
                    $instances = RRuleProcessor::expandRecurringEvent(
                        $eventArray,
                        $startDate,
                        $endDate,
                        $exdatesDecoded
                    );
                    $expanded = array_merge($expanded, $instances);
                } catch (\Exception $e) {
                    error_log("Recurring expansion failed for event {$eventArray['id']}: " . $e->getMessage());
                    $expanded[] = $eventArray;
                }
            } else {
                $eventStart = Carbon::parse($eventArray['start_datetime']);
                if ($eventStart->between($startDate, $endDate)) {
                    $expanded[] = $eventArray;
                }
            }
        }

      // 2. Expand series (event_series) + apply overrides (events.series_id)
        try {
            $seriesSql = "SELECT * FROM event_series WHERE status = 'published'";
            $seriesList = \HypnoseStammtisch\Database\Database::fetchAll($seriesSql);

            foreach ($seriesList as $series) {
                $seriesStart = Carbon::parse($series['start_date'])->startOfDay();
                $seriesEnd = $series['end_date'] ? Carbon::parse($series['end_date'])->endOfDay() : $endDate->copy();
              // Falls RRULE ein UNTIL enthält und keine end_date gesetzt ist, erweitere seriesEnd entsprechend (aber clamp später)
                if (empty($series['end_date']) && !empty($series['rrule']) && str_contains($series['rrule'], 'UNTIL=')) {
                    if (preg_match('/UNTIL=([0-9TZ]+)/', $series['rrule'], $m)) {
                        try {
                              $untilCandidate = Carbon::parse($m[1]);
                            if ($untilCandidate->gt($seriesEnd)) {
                                $seriesEnd = $untilCandidate->endOfDay();
                            }
                        } catch (\Exception $e) {
                          // ignore parse error
                        }
                    }
                }
                $seriesEnd = $seriesEnd->gt($endDate) ? $endDate->copy() : $seriesEnd; // clamp

              // Build pseudo event for RRULE expansion using start_time/end_time
                $startTime = $series['start_time'] ?? '00:00:00';
                $endTime = $series['end_time'] ?? null;
                if (!$endTime) {
                  // Ohne Endzeit ignorieren wir die Serie (inkonsistente Daten)
                    error_log('Serie ohne end_time ignoriert: ' . $series['id']);
                    continue;
                }
                $eventStartTemplate = Carbon::parse($series['start_date'] . ' ' . $startTime, 'Europe/Berlin');
                $eventEndTemplate = Carbon::parse($series['start_date'] . ' ' . $endTime, 'Europe/Berlin');

                $pseudo = [
                'id' => 'series_' . $series['id'],
                'title' => $series['title'],
                'description' => $series['description'],
                'start_datetime' => $eventStartTemplate->toDateTimeString(),
                'end_datetime' => $eventEndTemplate->toDateTimeString(),
                'timezone' => 'Europe/Berlin',
                'rrule' => $series['rrule'],
                'is_recurring' => true
                ];

                $exdatesRaw = $series['exdates'] ?? '[]';
                $exdatesDecoded = json_decode($exdatesRaw, true);
                if (!is_array($exdatesDecoded)) {
                    if (!empty($exdatesRaw)) {
                        error_log("Invalid exdates JSON for series {$series['id']}: " . $exdatesRaw);
                    }
                    $exdatesDecoded = [];
                }
                $exdates = $exdatesDecoded;
                $instances = RRuleProcessor::expandRecurringEvent($pseudo, $startDate, $endDate, $exdates);
                if (empty($instances)) {
                    error_log('RRULE yielded no instances for series ' . $series['id'] . ' within ' . $startDate->toDateString() . ' - ' . $endDate->toDateString() . ' | RRULE=' . $series['rrule']);
                }

              // Clip instances to series end (if defined)
                $instances = array_filter($instances, function ($i) use ($seriesStart, $seriesEnd) {
                    $dt = Carbon::parse($i['start_datetime']);
                    return $dt->between($seriesStart, $seriesEnd); // inclusive boundaries
                });

              // Fetch overrides for those instance dates (inkl. cancellations)
                $instanceDates = array_map(fn($i) => substr($i['start_datetime'], 0, 10), $instances);
                if (!empty($instanceDates)) {
                      $placeholders = implode(',', array_fill(0, count($instanceDates), '?'));
                      $overrideSql = "SELECT * FROM events WHERE series_id = ? AND instance_date IN ($placeholders)";
                      $params = array_merge([$series['id']], $instanceDates);
                      $overrides = \HypnoseStammtisch\Database\Database::fetchAll($overrideSql, $params);
                      $overrideMap = [];
                    foreach ($overrides as $ov) {
                        $overrideMap[$ov['instance_date']] = $ov;
                    }

                    foreach ($instances as $inst) {
                          $dateKey = substr($inst['start_datetime'], 0, 10);
                        if (isset($overrideMap[$dateKey])) {
                        // Cancellations werden nur aufgenommen falls Frontend sie darstellen soll; Option: komplett ausblenden
                            if (($overrideMap[$dateKey]['override_type'] ?? null) === 'cancelled') {
                                // Wir geben trotzdem das Cancel-Objekt aus (kann Frontend kennzeichnen)
                                $expanded[] = $overrideMap[$dateKey];
                            } else {
                                  $expanded[] = $overrideMap[$dateKey];
                            }
                        } else {
                            $inst['series_id'] = $series['id'];
                            $inst['instance_date'] = $dateKey;
                            $expanded[] = $inst;
                        }
                    }
                } else {
                    foreach ($instances as $inst) {
                        $dateKey = substr($inst['start_datetime'], 0, 10);
                        $inst['series_id'] = $series['id'];
                        $inst['instance_date'] = $dateKey;
                        $expanded[] = $inst;
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('Series expansion failed: ' . $e->getMessage());
        }

      // Sort combined list
        usort($expanded, fn($a, $b) => strtotime($a['start_datetime']) <=> strtotime($b['start_datetime']));
        return $expanded;
    }

  /**
   * Download single event as ICS
   * GET /api/events/{id}/ics
   */
    public function downloadICS(string $id): void
    {
        try {
            $event = Event::findById($id);
            if (!$event) {
                Response::json([
                'success' => false,
                'error' => 'Event not found'
                ], 404);
                return;
            }

            $eventArray = $this->eventToArray($event);
            ICSGenerator::outputSingleEvent($eventArray);
        } catch (\Exception $e) {
            error_log("Error generating ICS for event {$id}: " . $e->getMessage());
            Response::json([
            'success' => false,
            'error' => 'Failed to generate calendar file'
            ], 500);
        }
    }

  /**
   * Validate RRULE for recurring events
   * POST /api/events/validate-rrule
   */
    public function validateRRule(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['rrule']) || empty($input['rrule'])) {
                Response::json([
                'success' => false,
                'error' => 'RRULE is required'
                ], 400);
                return;
            }

            $errors = RRuleProcessor::validateRRule($input['rrule']);

            if (empty($errors)) {
                Response::json([
                'success' => true,
                'message' => 'RRULE is valid'
                ]);
            } else {
                Response::json([
                'success' => false,
                'errors' => $errors
                ], 400);
            }
        } catch (\Exception $e) {
            error_log("Error validating RRULE: " . $e->getMessage());
            Response::json([
            'success' => false,
            'error' => 'Failed to validate RRULE'
            ], 500);
        }
    }

  /**
   * Preview recurring event instances
   * POST /api/events/preview-recurring
   */
    public function previewRecurring(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            $required = ['rrule', 'start_datetime', 'end_datetime', 'title'];
            foreach ($required as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    Response::json([
                    'success' => false,
                    'error' => "Field '{$field}' is required"
                    ], 400);
                    return;
                }
            }

          // Create a temporary event for preview
            $tempEvent = [
            'id' => 'preview',
            'title' => $input['title'],
            'start_datetime' => $input['start_datetime'],
            'end_datetime' => $input['end_datetime'],
            'timezone' => $input['timezone'] ?? 'Europe/Berlin',
            'rrule' => $input['rrule'],
            'is_recurring' => true
            ];

            $startDate = Carbon::now();
            $endDate = Carbon::now()->addMonths(6); // 6 months preview
            $exdates = $input['exdates'] ?? [];

            $instances = RRuleProcessor::expandRecurringEvent(
                $tempEvent,
                $startDate,
                $endDate,
                $exdates
            );

          // Limit preview to first 20 instances
            $instances = array_slice($instances, 0, 20);

            Response::json([
            'success' => true,
            'data' => $instances,
            'meta' => [
              'total_shown' => count($instances),
              'preview_period' => [
              'start' => $startDate->toISOString(),
              'end' => $endDate->toISOString()
              ]
            ]
            ]);
        } catch (\Exception $e) {
            error_log("Error previewing recurring event: " . $e->getMessage());
            Response::json([
            'success' => false,
            'error' => 'Failed to preview recurring event: ' . $e->getMessage()
            ], 500);
        }
    }
}
