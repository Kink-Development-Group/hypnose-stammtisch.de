<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Middleware\AdminAuth;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\Validator;
use Exception;

/**
 * Admin events management controller
 */
class AdminEventsController
{
  /**
   * Get all events (including series)
   */
  public static function index(): void
  {
    AdminAuth::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    try {
      // Get single events
      $eventsSql = "SELECT e.*, NULL as series_title
                         FROM events e
                         WHERE e.series_id IS NULL
                         ORDER BY e.start_datetime DESC";
      $events = Database::fetchAll($eventsSql);

      // Get event series
      $seriesSql = "SELECT s.*, 'series' as event_type, COUNT(e.id) as generated_events_count
                         FROM event_series s
                         LEFT JOIN events e ON e.series_id = s.id
                         GROUP BY s.id
                         ORDER BY s.start_date DESC";
      $series = Database::fetchAll($seriesSql);

      // Combine and sort by creation date
      $allEvents = array_merge($events, $series);

      Response::success([
        'events' => $events,
        'series' => $series,
        'total' => count($allEvents)
      ]);
    } catch (Exception $e) {
      Response::error('Failed to fetch events: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Create new event or series
   */
  public static function create(): void
  {
    AdminAuth::requireAuth();
    // Only head, admin, or event role may create
    $user = AdminAuth::getCurrentUser();
    if (!$user || !in_array($user['role'], ['head', 'admin', 'event_manager'])) {
      Response::error('Insufficient permissions to create events', 403);
      return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Response::error('Method not allowed', 405);
      return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $eventType = $input['event_type'] ?? 'single';

    if ($eventType === 'series') {
      self::createSeries($input);
    } else {
      self::createSingleEvent($input);
    }
  }

  /**
   * Update existing event or series
   */
  public static function update(string $id): void
  {
    AdminAuth::requireAuth();
    $user = AdminAuth::getCurrentUser();
    if (!$user || !in_array($user['role'], ['head', 'admin', 'event_manager'])) {
      Response::error('Insufficient permissions to update events', 403);
      return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
      Response::error('Method not allowed', 405);
      return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // Check if it's a series or single event
    $checkSql = "SELECT 'event' as type FROM events WHERE id = ?
                     UNION
                     SELECT 'series' as type FROM event_series WHERE id = ?";
    $result = Database::fetchOne($checkSql, [$id, $id]);

    if (!$result) {
      Response::notFound(['message' => 'Event not found']);
      return;
    }

    if ($result['type'] === 'series') {
      self::updateSeries($id, $input);
    } else {
      self::updateSingleEvent($id, $input);
    }
  }

  /**
   * Delete event or series
   */
  public static function delete(string $id): void
  {
    AdminAuth::requireAuth();
    $user = AdminAuth::getCurrentUser();
    if (!$user) {
      Response::error('Authentication required', 401);
      return;
    }
    // event role has restricted delete rights
    $isEventManager = $user['role'] === 'event_manager';

    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
      Response::error('Method not allowed', 405);
      return;
    }

    try {
      Database::beginTransaction();

      // Check if it's a series or single event
      $checkSql = "SELECT 'event' as type FROM events WHERE id = ?
                         UNION
                         SELECT 'series' as type FROM event_series WHERE id = ?";
      $result = Database::fetchOne($checkSql, [$id, $id]);

      if (!$result) {
        Response::notFound(['message' => 'Event not found']);
        return;
      }

      if ($result['type'] === 'series') {
        // Delete series and all associated events
        Database::execute("DELETE FROM events WHERE series_id = ?", [$id]);
        Database::execute("DELETE FROM event_series WHERE id = ?", [$id]);
        $message = 'Event series deleted successfully';
      } else {
        if ($isEventManager) {
          // Check if event already ended (end_datetime < now UTC)
          $event = Database::fetchOne("SELECT end_datetime FROM events WHERE id = ?", [$id]);
          if (!$event) {
            Response::notFound(['message' => 'Event not found']);
            return;
          }
          $now = new \DateTime('now', new \DateTimeZone('UTC'));
          $end = new \DateTime($event['end_datetime'], new \DateTimeZone('UTC'));
          if ($end > $now) {
            Response::error('Event-Manager can only delete past events', 403);
            return;
          }
        }
        Database::execute("DELETE FROM events WHERE id = ?", [$id]);
        $message = 'Event deleted successfully';
      }

      Database::commit();
      Response::success(null, $message);
    } catch (Exception $e) {
      Database::rollback();
      Response::error('Failed to delete event: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Create single event
   */
  private static function createSingleEvent(array $input): void
  {
    $validator = new Validator($input);
    $validator->required(['title', 'start_datetime', 'end_datetime', 'category'])
      ->length('title', 3, 255)
      ->length('description', 0, 1000);

    if (!$validator->isValid()) {
      Response::error('Validation failed', 400, $validator->getErrors());
      return;
    }

    try {
      // Generate slug if not provided
      $slug = $input['slug'] ?? self::generateSlug($input['title']);

      // Check if slug exists
      if (self::slugExists($slug)) {
        Response::error('Event with this slug already exists', 400);
        return;
      }

      // Convert times to UTC
      $startUTC = self::convertToUTC($input['start_datetime'], $input['timezone'] ?? 'Europe/Berlin');
      $endUTC = self::convertToUTC($input['end_datetime'], $input['timezone'] ?? 'Europe/Berlin');

      $sql = "INSERT INTO events (
                title, slug, description, content, start_datetime, end_datetime, timezone,
                location_type, location_name, location_address, location_url,
                category, difficulty_level, max_participants, status, is_featured,
                requires_registration, organizer_name, organizer_email, tags
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

      $eventId = Database::insert($sql, [
        $input['title'],
        $slug,
        $input['description'] ?? null,
        $input['content'] ?? null,
        $startUTC,
        $endUTC,
        $input['timezone'] ?? 'Europe/Berlin',
        $input['location_type'] ?? 'physical',
        $input['location_name'] ?? null,
        $input['location_address'] ?? null,
        $input['location_url'] ?? null,
        $input['category'],
        $input['difficulty_level'] ?? 'all',
        $input['max_participants'] ?? null,
        $input['status'] ?? 'draft',
        isset($input['is_featured']) ? (int)$input['is_featured'] : 0,
        isset($input['requires_registration']) ? (int)$input['requires_registration'] : 1,
        $input['organizer_name'] ?? null,
        $input['organizer_email'] ?? null,
        isset($input['tags']) ? json_encode($input['tags']) : null
      ]);

      Response::success(['id' => $eventId], 'Event created successfully');
    } catch (Exception $e) {
      Response::error('Failed to create event: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Create event series
   */
  private static function createSeries(array $input): void
  {
    $validator = new Validator($input);
    $validator->required(['title', 'rrule', 'start_date'])
      ->length('title', 3, 255)
      ->length('description', 0, 1000);

    if (!$validator->isValid()) {
      Response::error('Validation failed', 400, $validator->getErrors());
      return;
    }

    try {
      // Generate slug if not provided
      $slug = $input['slug'] ?? self::generateSlug($input['title']);

      // Check if slug exists in series table
      $checkSql = "SELECT id FROM event_series WHERE slug = ?";
      if (Database::fetchOne($checkSql, [$slug])) {
        Response::error('Series with this slug already exists', 400);
        return;
      }

      $sql = "INSERT INTO event_series (
        title, slug, description, rrule, start_date, end_date, start_time, end_time, exdates,
        default_duration_minutes, default_location_type, default_location_name,
        default_location_address, default_category, default_max_participants,
        default_requires_registration, status, tags
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

      $seriesId = Database::insert($sql, [
        $input['title'],
        $slug,
        $input['description'] ?? null,
        $input['rrule'],
        $input['start_date'],
        // allow open-ended series (fortwährend) by accepting null end_date
        $input['end_date'] ?? null,
        $input['start_time'] ?? null,
        $input['end_time'] ?? null,
        isset($input['exdates']) ? json_encode($input['exdates']) : null,
        $input['default_duration_minutes'] ?? 120,
        $input['default_location_type'] ?? 'physical',
        $input['default_location_name'] ?? null,
        $input['default_location_address'] ?? null,
        $input['default_category'] ?? 'stammtisch',
        $input['default_max_participants'] ?? null,
        $input['default_requires_registration'] ?? true,
        $input['status'] ?? 'draft',
        isset($input['tags']) ? json_encode($input['tags']) : null
      ]);

      Response::success(['id' => $seriesId], 'Event series created successfully');
    } catch (Exception $e) {
      Response::error('Failed to create event series: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Update single event
   */
  private static function updateSingleEvent(string $id, array $input): void
  {
    $validator = new Validator($input);
    $validator->required(['title', 'start_datetime', 'end_datetime', 'category'])
      ->length('title', 3, 255);

    if (!$validator->isValid()) {
      Response::error('Validation failed', 400, $validator->getErrors());
      return;
    }

    try {
      // Convert times to UTC
      $startUTC = self::convertToUTC($input['start_datetime'], $input['timezone'] ?? 'Europe/Berlin');
      $endUTC = self::convertToUTC($input['end_datetime'], $input['timezone'] ?? 'Europe/Berlin');

      $sql = "UPDATE events SET
                title = ?, description = ?, content = ?, start_datetime = ?, end_datetime = ?,
                timezone = ?, location_type = ?, location_name = ?, location_address = ?,
                location_url = ?, category = ?, difficulty_level = ?, max_participants = ?,
                status = ?, is_featured = ?, requires_registration = ?, organizer_name = ?,
                organizer_email = ?, tags = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";

      Database::execute($sql, [
        $input['title'],
        $input['description'] ?? null,
        $input['content'] ?? null,
        $startUTC,
        $endUTC,
        $input['timezone'] ?? 'Europe/Berlin',
        $input['location_type'] ?? 'physical',
        $input['location_name'] ?? null,
        $input['location_address'] ?? null,
        $input['location_url'] ?? null,
        $input['category'],
        $input['difficulty_level'] ?? 'all',
        $input['max_participants'] ?? null,
        $input['status'] ?? 'draft',
        $input['is_featured'] ?? false,
        $input['requires_registration'] ?? true,
        $input['organizer_name'] ?? null,
        $input['organizer_email'] ?? null,
        isset($input['tags']) ? json_encode($input['tags']) : null,
        $id
      ]);

      Response::success(null, 'Event updated successfully');
    } catch (Exception $e) {
      Response::error('Failed to update event: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Update event series
   */
  private static function updateSeries(string $id, array $input): void
  {
    $validator = new Validator($input);
    $validator->required(['title', 'rrule', 'start_date'])
      ->length('title', 3, 255);

    if (!$validator->isValid()) {
      Response::error('Validation failed', 400, $validator->getErrors());
      return;
    }

    try {
      $sql = "UPDATE event_series SET
        title = ?, description = ?, rrule = ?, start_date = ?, end_date = ?, start_time = ?, end_time = ?,
        exdates = ?, default_duration_minutes = ?, default_location_type = ?,
        default_location_name = ?, default_location_address = ?, default_category = ?,
        default_max_participants = ?, default_requires_registration = ?, status = ?,
        tags = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?";

      Database::execute($sql, [
        $input['title'],
        $input['description'] ?? null,
        $input['rrule'],
        $input['start_date'],
        $input['end_date'] ?? null,
        $input['start_time'] ?? null,
        $input['end_time'] ?? null,
        isset($input['exdates']) ? json_encode($input['exdates']) : null,
        $input['default_duration_minutes'] ?? 120,
        $input['default_location_type'] ?? 'physical',
        $input['default_location_name'] ?? null,
        $input['default_location_address'] ?? null,
        $input['default_category'] ?? 'stammtisch',
        $input['default_max_participants'] ?? null,
        $input['default_requires_registration'] ?? true,
        $input['status'] ?? 'draft',
        isset($input['tags']) ? json_encode($input['tags']) : null,
        $id
      ]);

      Response::success(null, 'Event series updated successfully');
    } catch (Exception $e) {
      Response::error('Failed to update event series: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Generate URL-friendly slug
   */
  private static function generateSlug(string $title): string
  {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    // Add timestamp to ensure uniqueness
    return $slug . '-' . time();
  }

  /**
   * Check if slug exists
   */
  private static function slugExists(string $slug): bool
  {
    $eventExists = Database::fetchOne("SELECT id FROM events WHERE slug = ?", [$slug]);
    $seriesExists = Database::fetchOne("SELECT id FROM event_series WHERE slug = ?", [$slug]);

    return $eventExists || $seriesExists;
  }

  /**
   * Convert local datetime to UTC
   */
  private static function convertToUTC(string $datetime, string $timezone): string
  {
    $dt = new \DateTime($datetime, new \DateTimeZone($timezone));
    $dt->setTimezone(new \DateTimeZone('UTC'));
    return $dt->format('Y-m-d H:i:s');
  }

  /**
   * Create an override (individual instance) for an event series occurrence
   * POST /admin/events/series/{seriesId}/overrides
   * Body: { instance_date: 'YYYY-MM-DD', title?, start_time?, end_time?, description?, ... }
   */
  public static function createSeriesOverride(string $seriesId): void
  {
    AdminAuth::requireAuth();
    $user = AdminAuth::getCurrentUser();
    if (!$user || !in_array($user['role'], ['head', 'admin', 'event_manager'])) {
      Response::error('Insufficient permissions', 403);
      return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Response::error('Method not allowed', 405);
      return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $validator = new Validator($input);
    $validator->required(['instance_date']);

    if (!$validator->isValid()) {
      Response::error('Validation failed', 400, $validator->getErrors());
      return;
    }

    try {
      // Load series
      $series = Database::fetchOne('SELECT * FROM event_series WHERE id = ?', [$seriesId]);
      if (!$series) {
        Response::notFound(['message' => 'Series not found']);
        return;
      }

      $instanceDate = $input['instance_date'];
      // Build start/end datetime using provided times or series defaults
      $startTime = $input['start_time'] ?? $series['start_time'] ?? '00:00:00';
      $endTime = $input['end_time'] ?? $series['end_time'] ?? null;

      if ($endTime === null && isset($series['end_time'])) {
        $endTime = $series['end_time'];
      }

      if ($endTime === null) {
        // fallback duration via default_duration_minutes
        $duration = (int)($series['default_duration_minutes'] ?? 120);
        $endTime = (new \DateTime($startTime))->modify("+{$duration} minutes")->format('H:i:s');
      }

      $timezone = $input['timezone'] ?? 'Europe/Berlin';
      $startDtLocal = $instanceDate . ' ' . $startTime;
      $endDtLocal = $instanceDate . ' ' . $endTime;
      $startUTC = self::convertToUTC($startDtLocal, $timezone);
      $endUTC = self::convertToUTC($endDtLocal, $timezone);

      $sql = 'INSERT INTO events (
        title, slug, description, content, start_datetime, end_datetime, timezone,
        location_type, location_name, location_address, location_url, category, difficulty_level,
        max_participants, status, is_featured, requires_registration, organizer_name, organizer_email,
        tags, series_id, instance_date, parent_event_id
      ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

      $title = $input['title'] ?? $series['title'];
      $slug = self::generateSlug($title);
      $eventId = Database::insert($sql, [
        $title,
        $slug,
        $input['description'] ?? $series['description'] ?? null,
        $input['content'] ?? null,
        $startUTC,
        $endUTC,
        $timezone,
        $input['location_type'] ?? $series['default_location_type'] ?? 'physical',
        $input['location_name'] ?? $series['default_location_name'] ?? null,
        $input['location_address'] ?? $series['default_location_address'] ?? null,
        $input['location_url'] ?? null,
        $input['category'] ?? $series['default_category'] ?? 'stammtisch',
        $input['difficulty_level'] ?? 'all',
        $input['max_participants'] ?? $series['default_max_participants'] ?? null,
        $input['status'] ?? 'draft',
        $input['is_featured'] ?? 0,
        $input['requires_registration'] ?? ($series['default_requires_registration'] ?? 1),
        $input['organizer_name'] ?? null,
        $input['organizer_email'] ?? null,
        isset($input['tags']) ? json_encode($input['tags']) : $series['tags'],
        $seriesId,
        $instanceDate,
        null
      ]);

      Response::success(['id' => $eventId], 'Series override created');
    } catch (\Exception $e) {
      Response::error('Failed to create override: ' . $e->getMessage(), 500);
    }
  }

  /**
   * List overrides for a series
   */
  public static function listSeriesOverrides(string $seriesId): void
  {
    AdminAuth::requireAuth();
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }
    try {
      $rows = Database::fetchAll('SELECT * FROM events WHERE series_id = ? ORDER BY instance_date ASC', [$seriesId]);
      Response::success(['overrides' => $rows]);
    } catch (\Exception $e) {
      Response::error('Failed to list overrides: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Add EXDATE to series
   */
  public static function addSeriesExdate(string $seriesId): void
  {
    AdminAuth::requireAuth();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Response::error('Method not allowed', 405);
      return;
    }
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($input['date'])) {
      Response::error('date required', 400);
      return;
    }
    try {
      $series = Database::fetchOne('SELECT exdates FROM event_series WHERE id = ?', [$seriesId]);
      if (!$series) {
        Response::notFound(['message' => 'Series not found']);
        return;
      }
      $exdates = $series['exdates'] ? json_decode($series['exdates'], true) : [];
      if (!in_array($input['date'], $exdates)) {
        $exdates[] = $input['date'];
        sort($exdates);
      }
      Database::execute('UPDATE event_series SET exdates = ? WHERE id = ?', [json_encode($exdates), $seriesId]);
      Response::success(['exdates' => $exdates], 'EXDATE added');
    } catch (\Exception $e) {
      Response::error('Failed to add exdate: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Remove EXDATE
   */
  public static function removeSeriesExdate(string $seriesId): void
  {
    AdminAuth::requireAuth();
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
      Response::error('Method not allowed', 405);
      return;
    }
    $date = $_GET['date'] ?? null;
    if (!$date) {
      Response::error('date query param required', 400);
      return;
    }
    try {
      $series = Database::fetchOne('SELECT exdates FROM event_series WHERE id = ?', [$seriesId]);
      if (!$series) {
        Response::notFound(['message' => 'Series not found']);
        return;
      }
      $exdates = $series['exdates'] ? json_decode($series['exdates'], true) : [];
      $exdates = array_values(array_filter($exdates, fn($d) => $d !== $date));
      Database::execute('UPDATE event_series SET exdates = ? WHERE id = ?', [json_encode($exdates), $seriesId]);
      Response::success(['exdates' => $exdates], 'EXDATE removed');
    } catch (\Exception $e) {
      Response::error('Failed to remove exdate: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Get EXDATES list for a series
   */
  public static function getSeriesExdates(string $seriesId): void
  {
    AdminAuth::requireAuth();
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }
    try {
      $series = Database::fetchOne('SELECT exdates FROM event_series WHERE id = ?', [$seriesId]);
      if (!$series) {
        Response::notFound(['message' => 'Series not found']);
        return;
      }
      $exdates = $series['exdates'] ? json_decode($series['exdates'], true) : [];
      Response::success(['exdates' => $exdates]);
    } catch (\Exception $e) {
      Response::error('Failed to get exdates: ' . $e->getMessage(), 500);
    }
  }
}
