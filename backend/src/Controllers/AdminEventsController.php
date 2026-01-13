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

            // Get event series with aliased fields for frontend compatibility
            $seriesSql = "SELECT s.*, 'series' as event_type,
                         s.default_location_type as location_type,
                         s.default_location_name as location_name,
                         s.default_location_address as location_address,
                         s.default_category as category,
                         s.default_max_participants as max_participants,
                         s.default_requires_registration as requires_registration,
                         COUNT(e.id) as generated_events_count
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
        AdminAuth::requireCSRF();
        // Only head, admin, or event role may create
        $user = AdminAuth::getCurrentUser();
        if (!AdminAuth::userHasRole($user, AdminAuth::EVENT_MANAGEMENT_ROLES)) {
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
        AdminAuth::requireCSRF();
        $user = AdminAuth::getCurrentUser();
        if (!AdminAuth::userHasRole($user, AdminAuth::EVENT_MANAGEMENT_ROLES)) {
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
        AdminAuth::requireCSRF();
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
        error_log('[createSingleEvent] Input received: ' . json_encode($input));

        $validator = new Validator($input);
        $validator->required(['title', 'start_datetime', 'end_datetime', 'category'])
            ->length('title', 3, 255)
            ->length('description', 0, 10000);

        if (!$validator->isValid()) {
            error_log('[createSingleEvent] Validation failed: ' . json_encode($validator->getErrors()));
            Response::error('Validation failed', 400, $validator->getErrors());
            return;
        }

        try {
            // Validate time order if provided
            if (!empty($input['start_time']) && !empty($input['end_time'])) {
                $startT = \DateTime::createFromFormat('H:i', substr($input['start_time'], 0, 5));
                $endT = \DateTime::createFromFormat('H:i', substr($input['end_time'], 0, 5));
                if ($startT && $endT && $endT <= $startT) {
                    Response::error('end_time muss nach start_time liegen', 400);
                    return;
                }
            }
            // Generate slug if not provided (more entropy to avoid collisions)
            $slug = $input['slug'] ?? self::generateSlug($input['title']);

            // Check if slug exists
            if (self::slugExists($slug)) {
                Response::error('Event with this slug already exists', 400);
                return;
            }

            // Convert times to UTC
            $startUTC = self::convertToUTC($input['start_datetime'], $input['timezone'] ?? 'Europe/Berlin');
            $endUTC = self::convertToUTC($input['end_datetime'], $input['timezone'] ?? 'Europe/Berlin');

            // Validate chronological order
            if (strtotime($endUTC) <= strtotime($startUTC)) {
                Response::error('end_datetime muss nach start_datetime liegen', 400);
                return;
            }

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
            $msg = $e->getMessage();
            $inputPreview = $input;
            if (isset($inputPreview['content']) && is_string($inputPreview['content']) && strlen($inputPreview['content']) > 200) {
                $inputPreview['content'] = substr($inputPreview['content'], 0, 200) . '…';
            }
            // Versuche zusätzliche PDO-Fehlerinfos zu extrahieren
            if ($e instanceof \PDOException && method_exists($e, 'errorInfo') && !empty($e->errorInfo)) {
                error_log('[createSingleEvent][pdo] errorInfo: ' . json_encode($e->errorInfo));
            }
            error_log('[createSingleEvent] Insert attempt params: ' . json_encode([
                'title' => $input['title'] ?? null,
                'slug' => $slug ?? null,
                'startUTC' => $startUTC ?? null,
                'endUTC' => $endUTC ?? null,
                'timezone' => $input['timezone'] ?? 'Europe/Berlin',
                'category' => $input['category'] ?? null,
                'max_participants' => $input['max_participants'] ?? null,
            ]));
            error_log('[createSingleEvent] Exception: ' . $msg . ' | Input: ' . json_encode($inputPreview));
            if (str_contains(strtolower($msg), 'duplicate') && str_contains($msg, 'slug')) {
                Response::error('Slug collision – bitte erneut versuchen', 400);
                return;
            }
            $debug = \HypnoseStammtisch\Config\Config::get('app.debug', false);
            $extra = $debug ? ['exception' => $msg, 'input' => $inputPreview] : [];
            Response::error('Failed to create event: ' . ($debug ? $msg : 'internal error'), 500, $extra);
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
            ->length('description', 0, 10000);

        if (!$validator->isValid()) {
            Response::error('Validation failed', 400, $validator->getErrors());
            return;
        }

        // Map location_* fields to default_location_* if not already provided
        if (!isset($input['default_location_type']) && isset($input['location_type'])) {
            $input['default_location_type'] = $input['location_type'];
        }
        if (!isset($input['default_location_name']) && isset($input['location_name'])) {
            $input['default_location_name'] = $input['location_name'];
        }
        if (!isset($input['default_location_address']) && isset($input['location_address'])) {
            $input['default_location_address'] = $input['location_address'];
        }
        if (!isset($input['default_category']) && isset($input['category'])) {
            $input['default_category'] = $input['category'];
        }
        if (!isset($input['default_max_participants']) && isset($input['max_participants'])) {
            $input['default_max_participants'] = $input['max_participants'];
        }
        if (!isset($input['default_requires_registration']) && isset($input['requires_registration'])) {
            $input['default_requires_registration'] = $input['requires_registration'];
        }

        try {
            // Generate slug if not provided (more entropy)
            $slug = $input['slug'] ?? self::generateSlug($input['title']);

            // Check if slug exists in series table
            $checkSql = "SELECT id FROM event_series WHERE slug = ?";
            if (Database::fetchOne($checkSql, [$slug])) {
                Response::error('Series with this slug already exists', 400);
                return;
            }

            // Normalisiere leere Strings -> null
            $startTime = isset($input['start_time']) && trim((string)$input['start_time']) !== '' ? substr($input['start_time'], 0, 5) : null;
            $endTime   = isset($input['end_time']) && trim((string)$input['end_time']) !== '' ? substr($input['end_time'], 0, 5) : null;
            $endDate   = isset($input['end_date']) && trim((string)$input['end_date']) !== '' ? $input['end_date'] : null;

            // Zeitliche Plausibilitätsprüfung falls beide gesetzt
            if ($startTime && $endTime) {
                $st = \DateTime::createFromFormat('H:i', $startTime);
                $et = \DateTime::createFromFormat('H:i', $endTime);
                if ($st && $et && $et <= $st) {
                    Response::error('end_time muss nach start_time liegen', 400);
                    return;
                }
            }

            $exdatesJson = isset($input['exdates']) ? json_encode($input['exdates']) : null;
            $tagsJson    = isset($input['tags']) ? json_encode($input['tags']) : null;

            $sql = "INSERT INTO event_series (
        title, slug, description, rrule, start_date, end_date, start_time, end_time, exdates,
        default_location_type, default_location_name,
        default_location_address, default_category, default_max_participants,
        default_requires_registration, status, tags
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            try {
                $seriesId = Database::insert($sql, [
                    $input['title'],
                    $slug,
                    $input['description'] ?? null,
                    $input['rrule'],
                    $input['start_date'],
                    $endDate,
                    $startTime,
                    $endTime,
                    $exdatesJson,
                    $input['default_location_type'] ?? 'physical',
                    $input['default_location_name'] ?? null,
                    $input['default_location_address'] ?? null,
                    $input['default_category'] ?? 'stammtisch',
                    $input['default_max_participants'] ?? null,
                    $input['default_requires_registration'] ?? true,
                    $input['status'] ?? 'draft',
                    $tagsJson
                ]);
            } catch (\Exception $inner) {
                $innerMsg = $inner->getMessage();
                // Fallback: wenn Migration 003 noch nicht gelaufen (start_time / end_time unbekannt)
                if (str_contains(strtolower($innerMsg), 'unknown column') && (str_contains($innerMsg, 'start_time') || str_contains($innerMsg, 'end_time'))) {
                    // Versuche Insert ohne Zeit-Spalten
                    $fallbackSql = "INSERT INTO event_series (
            title, slug, description, rrule, start_date, end_date, exdates,
            default_location_type, default_location_name, default_location_address, default_category, default_max_participants,
            default_requires_registration, status, tags
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    try {
                        $seriesId = Database::insert($fallbackSql, [
                            $input['title'],
                            $slug,
                            $input['description'] ?? null,
                            $input['rrule'],
                            $input['start_date'],
                            $endDate,
                            $exdatesJson,
                            $input['default_location_type'] ?? 'physical',
                            $input['default_location_name'] ?? null,
                            $input['default_location_address'] ?? null,
                            $input['default_category'] ?? 'stammtisch',
                            $input['default_max_participants'] ?? null,
                            $input['default_requires_registration'] ?? true,
                            $input['status'] ?? 'draft',
                            $tagsJson
                        ]);
                        // Informiere Client, dass Migration fehlt
                        Response::success([
                            'id' => $seriesId,
                            'warning' => 'Serie erstellt, aber Migration 003 (start_time/end_time) scheint nicht angewendet. Bitte Migration ausführen.'
                        ], 'Event series created (ohne Zeit-Spalten)');
                        return;
                    } catch (\Exception $fallbackEx) {
                        throw $fallbackEx; // gehe in äußeres catch
                    }
                }
                throw $inner; // rethrow, wird unten gefangen
            }

            Response::success(['id' => $seriesId], 'Event series created successfully');
        } catch (Exception $e) {
            $msg = $e->getMessage();
            error_log('[createSeries] Exception: ' . $msg . ' | Input: ' . json_encode($input));
            if (str_contains(strtolower($msg), 'duplicate') && str_contains($msg, 'slug')) {
                Response::error('Series slug collision – bitte erneut versuchen', 400);
                return;
            }
            Response::error('Failed to create event series: ' . $msg, 500, [
                'hint' => 'Falls "Unknown column start_time" auftritt: php backend/migrations/migrate.php migrate ausführen.'
            ]);
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

        // Map location_* fields to default_location_* if not already provided
        if (!isset($input['default_location_type']) && isset($input['location_type'])) {
            $input['default_location_type'] = $input['location_type'];
        }
        if (!isset($input['default_location_name']) && isset($input['location_name'])) {
            $input['default_location_name'] = $input['location_name'];
        }
        if (!isset($input['default_location_address']) && isset($input['location_address'])) {
            $input['default_location_address'] = $input['location_address'];
        }
        if (!isset($input['default_category']) && isset($input['category'])) {
            $input['default_category'] = $input['category'];
        }
        if (!isset($input['default_max_participants']) && isset($input['max_participants'])) {
            $input['default_max_participants'] = $input['max_participants'];
        }
        if (!isset($input['default_requires_registration']) && isset($input['requires_registration'])) {
            $input['default_requires_registration'] = $input['requires_registration'];
        }

        try {
            // Normalisierung & Validierung Zeiten
            $startTime = isset($input['start_time']) && trim((string)$input['start_time']) !== '' ? substr($input['start_time'], 0, 5) : null;
            $endTime   = isset($input['end_time']) && trim((string)$input['end_time']) !== '' ? substr($input['end_time'], 0, 5) : null;
            if ($startTime && $endTime) {
                $startT = \DateTime::createFromFormat('H:i', $startTime);
                $endT = \DateTime::createFromFormat('H:i', $endTime);
                if ($startT && $endT && $endT <= $startT) {
                    Response::error('end_time muss nach start_time liegen', 400);
                    return;
                }
            }

            $exdatesJson = isset($input['exdates']) ? json_encode($input['exdates']) : null;
            $tagsJson = isset($input['tags']) ? json_encode($input['tags']) : null;
            $endDate = isset($input['end_date']) && trim((string)$input['end_date']) !== '' ? $input['end_date'] : null;

            $sql = "UPDATE event_series SET
        title = ?, description = ?, rrule = ?, start_date = ?, end_date = ?, start_time = ?, end_time = ?,
        exdates = ?, default_location_type = ?,
        default_location_name = ?, default_location_address = ?, default_category = ?,
        default_max_participants = ?, default_requires_registration = ?, status = ?,
        tags = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?";

            try {
                Database::execute($sql, [
                    $input['title'],
                    $input['description'] ?? null,
                    $input['rrule'],
                    $input['start_date'],
                    $endDate,
                    $startTime,
                    $endTime,
                    $exdatesJson,
                    $input['default_location_type'] ?? 'physical',
                    $input['default_location_name'] ?? null,
                    $input['default_location_address'] ?? null,
                    $input['default_category'] ?? 'stammtisch',
                    $input['default_max_participants'] ?? null,
                    $input['default_requires_registration'] ?? true,
                    $input['status'] ?? 'draft',
                    $tagsJson,
                    $id
                ]);
            } catch (\Exception $inner) {
                $msg = $inner->getMessage();
                if (str_contains(strtolower($msg), 'unknown column') && (str_contains($msg, 'start_time') || str_contains($msg, 'end_time'))) {
                    // Fallback ohne Zeitspalten (Migration 003 fehlt)
                    $fallbackSql = "UPDATE event_series SET
              title = ?, description = ?, rrule = ?, start_date = ?, end_date = ?,
              exdates = ?, default_location_type = ?, default_location_name = ?, default_location_address = ?, default_category = ?,
              default_max_participants = ?, default_requires_registration = ?, status = ?, tags = ?, updated_at = CURRENT_TIMESTAMP
              WHERE id = ?";
                    Database::execute($fallbackSql, [
                        $input['title'],
                        $input['description'] ?? null,
                        $input['rrule'],
                        $input['start_date'],
                        $endDate,
                        $exdatesJson,
                        $input['default_location_type'] ?? 'physical',
                        $input['default_location_name'] ?? null,
                        $input['default_location_address'] ?? null,
                        $input['default_category'] ?? 'stammtisch',
                        $input['default_max_participants'] ?? null,
                        $input['default_requires_registration'] ?? true,
                        $input['status'] ?? 'draft',
                        $tagsJson,
                        $id
                    ]);
                    Response::success(null, 'Event series updated (ohne Zeit-Spalten – bitte Migration 003 ausführen)');
                    return;
                }
                throw $inner;
            }

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
        $base = strtolower(trim($title));
        $base = preg_replace('/[^a-z0-9-]/', '-', $base);
        $base = preg_replace('/-+/', '-', $base);
        $base = trim($base, '-');
        // More entropy: datetime + random hex; robust fallback wenn random_bytes nicht verfügbar
        try {
            $rand = substr(bin2hex(random_bytes(4)), 0, 8);
        } catch (\Throwable $e) {
            $rand = substr(sha1(uniqid((string)mt_rand(), true)), 0, 8);
        }
        $suffix = date('YmdHis') . '-' . $rand;
        return $base . '-' . $suffix;
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
        AdminAuth::requireCSRF();
        $user = AdminAuth::getCurrentUser();
        if (!AdminAuth::userHasRole($user, AdminAuth::EVENT_MANAGEMENT_ROLES)) {
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

            // Wenn keine Endzeit vorhanden ist, kein automatisches Fallback mehr (Pflicht: Nutzer definiert Zeiten in Serie oder Override)
            if ($endTime === null) {
                Response::error('Serie oder Override benötigt end_time (Standarddauer entfernt)', 400);
                return;
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
        tags, series_id, instance_date, parent_event_id, override_type
      ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

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
                null,
                'changed'
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
     * Delete a series override (individual instance event)
     */
    public static function deleteSeriesOverride(string $seriesId, string $overrideId): void
    {
        AdminAuth::requireAuth();
        AdminAuth::requireCSRF();
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            Response::error('Method not allowed', 405);
            return;
        }
        try {
            $row = Database::fetchOne('SELECT id FROM events WHERE id = ? AND series_id = ?', [$overrideId, $seriesId]);
            if (!$row) {
                Response::notFound(['message' => 'Override not found']);
                return;
            }
            Database::execute('DELETE FROM events WHERE id = ?', [$overrideId]);
            Response::success(null, 'Override deleted');
        } catch (\Exception $e) {
            Response::error('Failed to delete override: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add EXDATE to series
     */
    public static function addSeriesExdate(string $seriesId): void
    {
        AdminAuth::requireAuth();
        AdminAuth::requireCSRF();
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
        AdminAuth::requireCSRF();
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

    /**
     * Cancel a single instance of a series (creates or updates override_type=cancelled)
     * POST /admin/events/series/{seriesId}/cancel
     * Body: { instance_date: 'YYYY-MM-DD', reason?: string }
     */
    public static function cancelSeriesInstance(string $seriesId): void
    {
        AdminAuth::requireAuth();
        AdminAuth::requireCSRF();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($input['instance_date'])) {
            Response::error('instance_date required', 400);
            return;
        }
        try {
            $series = Database::fetchOne('SELECT * FROM event_series WHERE id = ?', [$seriesId]);
            if (!$series) {
                Response::notFound(['message' => 'Series not found']);
                return;
            }
            $instanceDate = $input['instance_date'];
            // Versuche bestehendes Override zu finden
            $existing = Database::fetchOne('SELECT id, override_type FROM events WHERE series_id = ? AND instance_date = ?', [$seriesId, $instanceDate]);
            if ($existing) {
                Database::execute('UPDATE events SET override_type = ?, cancellation_reason = ?, status = ? WHERE id = ?', [
                    'cancelled',
                    $input['reason'] ?? null,
                    'cancelled',
                    $existing['id']
                ]);
                Response::success(['id' => $existing['id']], 'Instance cancelled');
                return;
            }
            // Neues Cancel-Override (minimal – nutzt Serien-Metadaten)
            if (!$series['start_time'] || !$series['end_time']) {
                Response::error('Serie benötigt start_time & end_time für Cancel-Instanz (Migration 003+)', 400);
                return;
            }
            $tz = 'Europe/Berlin';
            $startUTC = self::convertToUTC($instanceDate . ' ' . $series['start_time'], $tz);
            $endUTC = self::convertToUTC($instanceDate . ' ' . $series['end_time'], $tz);
            $insertSql = 'INSERT INTO events (title, slug, start_datetime, end_datetime, timezone, category, status, tags, series_id, instance_date, parent_event_id, override_type, cancellation_reason) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $title = $series['title'] . ' (abgesagt)';
            $slug = self::generateSlug($title . '-' . $instanceDate);
            $id = Database::insert($insertSql, [
                $title,
                $slug,
                $startUTC,
                $endUTC,
                $tz,
                $series['default_category'] ?? 'stammtisch',
                'cancelled',
                $series['tags'],
                $seriesId,
                $instanceDate,
                null,
                'cancelled',
                $input['reason'] ?? null
            ]);
            Response::success(['id' => $id], 'Instance cancelled');
        } catch (\Exception $e) {
            Response::error('Failed to cancel instance: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Restore a cancelled instance (removes cancellation override or switches changed->active)
     * DELETE /admin/events/series/{seriesId}/cancel?instance_date=YYYY-MM-DD
     */
    public static function restoreSeriesInstance(string $seriesId): void
    {
        AdminAuth::requireAuth();
        AdminAuth::requireCSRF();
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            Response::error('Method not allowed', 405);
            return;
        }
        $instanceDate = $_GET['instance_date'] ?? null;
        if (!$instanceDate) {
            Response::error('instance_date query param required', 400);
            return;
        }
        try {
            $row = Database::fetchOne('SELECT id, override_type FROM events WHERE series_id = ? AND instance_date = ?', [$seriesId, $instanceDate]);
            if (!$row) {
                Response::success(null, 'Nothing to restore');
                return;
            }
            if ($row['override_type'] === 'cancelled') {
                // vollständige Cancel-Override entfernen
                Database::execute('DELETE FROM events WHERE id = ?', [$row['id']]);
                Response::success(null, 'Cancellation removed');
                return;
            }
            // Wenn geändert, nur Status zurücksetzen (kein echtes cancel restore nötig)
            Response::success(null, 'No cancellation to remove');
        } catch (\Exception $e) {
            Response::error('Failed to restore instance: ' . $e->getMessage(), 500);
        }
    }
}
