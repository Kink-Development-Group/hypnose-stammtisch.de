<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Middleware\AdminAuth;
use HypnoseStammtisch\Models\KnownEventSeries;
use HypnoseStammtisch\Utils\Response;

/**
 * Management of the "Bekannte Event-Reihen" cards on the home page.
 *
 * Restricted to head admins and admins. Event managers are deliberately left
 * out: they curate their own events, but which series the home page advertises
 * is an editorial decision for the two admin roles.
 */
class AdminKnownEventSeriesController
{
    /** Upper bounds mirroring the column widths in migration 015. */
    private const MAX_LIST_ENTRIES = 12;
    private const MAX_LIST_ENTRY_LENGTH = 120;

    /**
     * Only head admins and admins may manage the section.
     */
    private static function requireSeriesAdmin(): void
    {
        AdminAuth::requireAuth();

        $user = AdminAuth::getCurrentUser();
        if (!AdminAuth::userHasRole($user, AdminAuth::EVENT_FULL_ACCESS_ROLES)) {
            Response::error('Insufficient permissions. Head admin or admin role required.', 403);
            exit;
        }
    }

    /**
     * GET /api/admin/known-event-series
     */
    public static function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        self::requireSeriesAdmin();

        try {
            $series = KnownEventSeries::getAllForAdmin();
            Response::success(
                array_map(fn($entry) => $entry->toArray(), $series),
                'Known event series retrieved successfully'
            );
        } catch (\Exception $e) {
            error_log('Error fetching known event series for admin: ' . $e->getMessage());
            Response::error('Failed to fetch known event series', 500);
        }
    }

    /**
     * GET /api/admin/known-event-series/linkable-series
     *
     * The recurring series an entry can be linked to, for the "next date"
     * dropdown.
     */
    public static function linkableSeries(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        self::requireSeriesAdmin();

        try {
            $rows = Database::fetchAll(
                "SELECT id, title, status FROM event_series ORDER BY title ASC"
            );

            $payload = array_map(fn(array $row) => [
                'id' => $row['id'],
                'title' => $row['title'],
                'status' => $row['status'],
            ], $rows);

            Response::success($payload, 'Linkable event series retrieved successfully');
        } catch (\Exception $e) {
            error_log('Error fetching linkable event series: ' . $e->getMessage());
            Response::error('Failed to fetch linkable event series', 500);
        }
    }

    /**
     * GET /api/admin/known-event-series/{id}
     */
    public static function show(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        self::requireSeriesAdmin();

        try {
            $series = KnownEventSeries::getById($id);
            if (!$series) {
                Response::notFound(['message' => 'Known event series not found']);
                return;
            }

            Response::success($series->toArray(), 'Known event series retrieved successfully');
        } catch (\Exception $e) {
            error_log('Error fetching known event series: ' . $e->getMessage());
            Response::error('Failed to fetch known event series', 500);
        }
    }

    /**
     * POST /api/admin/known-event-series
     */
    public static function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        self::requireSeriesAdmin();
        AdminAuth::requireCSRF();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $errors = self::collectValidationErrors($input);
        if (!empty($errors)) {
            Response::error('Validation failed', 400, $errors);
            return;
        }

        $linkError = self::checkLinkedSeries($input);
        if ($linkError !== null) {
            Response::error('Validation failed', 400, $linkError);
            return;
        }

        try {
            $currentUser = AdminAuth::getCurrentUser();
            $nextEventSource = $input['next_event_source'] ?? 'none';

            $series = new KnownEventSeries(
                title: trim((string)$input['title']),
                location: trim((string)$input['location']),
                frequency: trim((string)$input['frequency']),
                description: trim((string)($input['description'] ?? '')),
                formats: KnownEventSeries::sanitizeStringList($input['formats'] ?? []),
                price: self::emptyToNull($input['price'] ?? null),
                tags: KnownEventSeries::sanitizeStringList($input['tags'] ?? []),
                detailUrl: self::normalizeDetailUrl($input['detail_url'] ?? null),
                nextEventSource: $nextEventSource,
                nextEventText: $nextEventSource === 'manual'
                    ? self::emptyToNull($input['next_event_text'] ?? null)
                    : null,
                linkedSeriesId: $nextEventSource === 'auto'
                    ? self::emptyToNull($input['linked_series_id'] ?? null)
                    : null,
                sortOrder: isset($input['sort_order'])
                    ? (int)$input['sort_order']
                    : KnownEventSeries::getMaxSortOrder() + 1,
                status: $input['status'] ?? 'draft',
                isActive: array_key_exists('is_active', $input) ? (bool)$input['is_active'] : true,
                createdBy: $currentUser['id'] ?? null
            );

            $seriesId = $series->create();

            if ($seriesId) {
                $created = KnownEventSeries::getById($seriesId);
                Response::success($created?->toArray(), 'Known event series created successfully', 201);
            } else {
                Response::error('Failed to create known event series', 500);
            }
        } catch (\Exception $e) {
            error_log('Error creating known event series: ' . $e->getMessage());
            Response::error('Failed to create known event series', 500);
        }
    }

    /**
     * PUT /api/admin/known-event-series/{id}
     */
    public static function update(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            Response::error('Method not allowed', 405);
            return;
        }

        self::requireSeriesAdmin();
        AdminAuth::requireCSRF();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $series = KnownEventSeries::getById($id);
        if (!$series) {
            Response::notFound(['message' => 'Known event series not found']);
            return;
        }

        $errors = self::collectValidationErrors($input, true);
        if (!empty($errors)) {
            Response::error('Validation failed', 400, $errors);
            return;
        }

        // The "next date" mode decides which of its two companion fields matters,
        // so validate the mode that will be stored, not just the one that was sent.
        $effectiveSource = $input['next_event_source'] ?? $series->nextEventSource;
        // `array_key_exists`, not `??`: an explicit null is a request to drop the
        // link, and must be validated as such instead of falling back to the
        // stored id — otherwise the update would store a dangling `auto` row.
        $effectiveLinkedId = array_key_exists('linked_series_id', $input)
            ? $input['linked_series_id']
            : $series->linkedSeriesId;
        $linkError = self::checkLinkedSeries([
            'next_event_source' => $effectiveSource,
            'linked_series_id' => $effectiveLinkedId,
        ]);
        if ($linkError !== null) {
            Response::error('Validation failed', 400, $linkError);
            return;
        }

        // Same reasoning for the manual text: a request that only clears it would
        // otherwise leave a `manual` row with nothing to show.
        if ($effectiveSource === 'manual') {
            $effectiveText = array_key_exists('next_event_text', $input)
                ? $input['next_event_text']
                : $series->nextEventText;
            $textError = self::manualTextError($effectiveText);
            if ($textError !== null) {
                Response::error('Validation failed', 400, ['next_event_text' => $textError]);
                return;
            }
        }

        try {
            if (isset($input['title'])) {
                $series->title = trim((string)$input['title']);
            }
            if (isset($input['location'])) {
                $series->location = trim((string)$input['location']);
            }
            if (isset($input['frequency'])) {
                $series->frequency = trim((string)$input['frequency']);
            }
            if (isset($input['description'])) {
                $series->description = trim((string)$input['description']);
            }
            if (isset($input['formats'])) {
                $series->formats = KnownEventSeries::sanitizeStringList($input['formats']);
            }
            if (array_key_exists('price', $input)) {
                $series->price = self::emptyToNull($input['price']);
            }
            if (isset($input['tags'])) {
                $series->tags = KnownEventSeries::sanitizeStringList($input['tags']);
            }
            if (isset($input['detail_url'])) {
                $series->detailUrl = self::normalizeDetailUrl($input['detail_url']);
            }
            if (isset($input['next_event_text'])) {
                $series->nextEventText = self::emptyToNull($input['next_event_text']);
            }
            if (array_key_exists('linked_series_id', $input)) {
                $series->linkedSeriesId = self::emptyToNull($input['linked_series_id']);
            }
            if (isset($input['next_event_source'])) {
                $series->nextEventSource = $input['next_event_source'];
            }
            if (isset($input['sort_order'])) {
                $series->sortOrder = (int)$input['sort_order'];
            }
            if (isset($input['status'])) {
                $series->status = $input['status'];
            }
            if (array_key_exists('is_active', $input)) {
                $series->isActive = (bool)$input['is_active'];
            }

            // Drop the companion field the stored mode does not use, so a later
            // mode switch cannot resurrect a stale date.
            if ($series->nextEventSource !== 'manual') {
                $series->nextEventText = null;
            }
            if ($series->nextEventSource !== 'auto') {
                $series->linkedSeriesId = null;
            }

            if ($series->update()) {
                $updated = KnownEventSeries::getById($id);
                Response::success($updated?->toArray(), 'Known event series updated successfully');
            } else {
                Response::error('Failed to update known event series', 500);
            }
        } catch (\Exception $e) {
            error_log('Error updating known event series: ' . $e->getMessage());
            Response::error('Failed to update known event series', 500);
        }
    }

    /**
     * DELETE /api/admin/known-event-series/{id}
     */
    public static function delete(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            Response::error('Method not allowed', 405);
            return;
        }

        self::requireSeriesAdmin();
        AdminAuth::requireCSRF();

        try {
            $series = KnownEventSeries::getById($id);
            if (!$series) {
                Response::notFound(['message' => 'Known event series not found']);
                return;
            }

            if ($series->delete()) {
                Response::success(null, 'Known event series deleted successfully');
            } else {
                Response::error('Failed to delete known event series', 500);
            }
        } catch (\Exception $e) {
            error_log('Error deleting known event series: ' . $e->getMessage());
            Response::error('Failed to delete known event series', 500);
        }
    }

    /**
     * POST /api/admin/known-event-series/reorder
     *
     * Body: `{ "ids": [...] }` — the ids in the order the home page should show
     * them.
     */
    public static function reorder(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        self::requireSeriesAdmin();
        AdminAuth::requireCSRF();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $ids = self::sanitizeIdList($input['ids'] ?? null);

        if (empty($ids)) {
            Response::error('Validation failed', 400, ['ids' => 'At least one valid ID is required']);
            return;
        }

        try {
            if (KnownEventSeries::reorder($ids)) {
                $series = KnownEventSeries::getAllForAdmin();
                Response::success(
                    array_map(fn($entry) => $entry->toArray(), $series),
                    'Known event series reordered successfully'
                );
            } else {
                Response::error('Failed to reorder known event series', 500);
            }
        } catch (\Exception $e) {
            error_log('Error reordering known event series: ' . $e->getMessage());
            Response::error('Failed to reorder known event series', 500);
        }
    }

    /**
     * Validate a create or update payload.
     *
     * `$partial` skips the required checks for fields the request did not send,
     * which is what a PUT with a handful of changed fields needs. Free of
     * database and session access so the rules stay unit-testable.
     *
     * @param array<string, mixed> $input
     * @return array<string, string> Field name → message, empty when valid
     */
    public static function collectValidationErrors(array $input, bool $partial = false): array
    {
        $errors = [];

        $textRules = [
            'title' => [3, 255],
            'location' => [2, 255],
            'frequency' => [2, 255],
        ];

        foreach ($textRules as $field => [$min, $max]) {
            if (!array_key_exists($field, $input)) {
                if (!$partial) {
                    $errors[$field] = ucfirst($field) . ' is required';
                }
                continue;
            }

            if (!is_string($input[$field])) {
                $errors[$field] = ucfirst($field) . ' must be a string';
                continue;
            }

            $length = mb_strlen(trim($input[$field]));
            if ($length < $min || $length > $max) {
                $errors[$field] = ucfirst($field) . " must be between {$min} and {$max} characters";
            }
        }

        if (isset($input['description']) && !is_string($input['description'])) {
            $errors['description'] = 'Description must be a string';
        } elseif (isset($input['description']) && mb_strlen($input['description']) > 2000) {
            $errors['description'] = 'Description must not exceed 2000 characters';
        }

        if (isset($input['price']) && (!is_string($input['price']) || mb_strlen($input['price']) > 100)) {
            $errors['price'] = 'Price must be a string of at most 100 characters';
        }

        foreach (['formats', 'tags'] as $listField) {
            if (!isset($input[$listField])) {
                continue;
            }

            if (!is_array($input[$listField])) {
                $errors[$listField] = ucfirst($listField) . ' must be a list';
                continue;
            }

            if (count($input[$listField]) > self::MAX_LIST_ENTRIES) {
                $errors[$listField] = ucfirst($listField) . ' must not exceed ' . self::MAX_LIST_ENTRIES . ' entries';
                continue;
            }

            foreach ($input[$listField] as $entry) {
                if (!is_string($entry) || mb_strlen($entry) > self::MAX_LIST_ENTRY_LENGTH) {
                    $errors[$listField] = ucfirst($listField)
                        . ' entries must be strings of at most ' . self::MAX_LIST_ENTRY_LENGTH . ' characters';
                    break;
                }
            }
        }

        if (isset($input['detail_url'])) {
            $url = is_string($input['detail_url']) ? trim($input['detail_url']) : '';
            $isInternal = str_starts_with($url, '/');
            $isExternal = str_starts_with($url, 'https://') || str_starts_with($url, 'http://');

            if ($url !== '' && (!($isInternal || $isExternal) || mb_strlen($url) > 500)) {
                $errors['detail_url'] = 'Detail URL must be an internal path or an http(s) URL of at most 500 characters';
            }
        }

        // The shape of the text is checked whenever it is sent, not only when the
        // request also names the mode: a partial update may touch the text alone
        // on a row that is already `manual`, and the column stops at 255.
        if (isset($input['next_event_text'])) {
            $text = $input['next_event_text'];
            if (!is_string($text) || mb_strlen($text) > 255) {
                $errors['next_event_text'] = 'Next event text must be a string of at most 255 characters';
            }
        }

        if (isset($input['next_event_source'])) {
            if (!in_array($input['next_event_source'], KnownEventSeries::NEXT_EVENT_SOURCES, true)) {
                $errors['next_event_source'] = 'Next event source must be auto, manual, or none';
            } elseif ($input['next_event_source'] === 'manual') {
                $textError = self::manualTextError($input['next_event_text'] ?? null);
                if ($textError !== null) {
                    $errors['next_event_text'] = $textError;
                }
            }
        }

        if (isset($input['status']) && !in_array($input['status'], KnownEventSeries::STATUSES, true)) {
            $errors['status'] = 'Status must be draft, published, or archived';
        }

        if (isset($input['sort_order'])) {
            if (!is_numeric($input['sort_order']) || (int)$input['sort_order'] < 0) {
                $errors['sort_order'] = 'Sort order must be a non-negative number';
            }
        }

        // Rejected rather than cast: `(bool)"false"` is true, so a stray string
        // would publish a series on the home page that was meant to stay hidden.
        if (array_key_exists('is_active', $input) && !is_bool($input['is_active'])) {
            if (!in_array($input['is_active'], [0, 1, '0', '1'], true)) {
                $errors['is_active'] = 'Is active must be a boolean';
            }
        }

        return $errors;
    }

    /**
     * A `manual` next date needs a usable text, or the card silently loses the
     * line it exists for.
     *
     * @return string|null The message, or null when the text is fine
     */
    private static function manualTextError(mixed $text): ?string
    {
        if (!is_string($text) || trim($text) === '' || mb_strlen($text) > 255) {
            return 'A manual next date needs a text of 1 to 255 characters';
        }

        return null;
    }

    /**
     * A series set to `auto` must point at an existing recurring series —
     * otherwise the card would silently never show a date.
     *
     * @param array<string, mixed> $input
     * @return array<string, string>|null
     */
    private static function checkLinkedSeries(array $input): ?array
    {
        if (($input['next_event_source'] ?? null) !== 'auto') {
            return null;
        }

        $linkedId = $input['linked_series_id'] ?? null;
        if (!is_string($linkedId) || trim($linkedId) === '') {
            return ['linked_series_id' => 'An automatic next date needs a linked event series'];
        }

        if (!KnownEventSeries::linkedSeriesExists(trim($linkedId))) {
            return ['linked_series_id' => 'The linked event series does not exist'];
        }

        return null;
    }

    /**
     * @return string[]
     */
    private static function sanitizeIdList(mixed $ids): array
    {
        if (!is_array($ids)) {
            return [];
        }

        $sanitized = [];
        foreach ($ids as $id) {
            if (is_string($id) && preg_match('/^[a-zA-Z0-9\-]{1,36}$/', $id)) {
                $sanitized[] = $id;
            }
        }

        return $sanitized;
    }

    private static function emptyToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string)$value);
        return $trimmed === '' ? null : $trimmed;
    }

    private static function normalizeDetailUrl(mixed $value): string
    {
        $url = self::emptyToNull($value);
        return $url ?? '/events';
    }
}
