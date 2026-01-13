<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Middleware\AdminAuth;
use HypnoseStammtisch\Models\StammtischLocation;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\Validator;

/**
 * Admin Stammtisch Location Management Controller
 * Accessible by head admins, admins, and event managers
 */
class AdminStammtischLocationController
{
    /**
     * Check if current user is authorized (head admin, admin, or event manager)
     */
    private static function requireLocationAdmin(): void
    {
        AdminAuth::requireAuth();

        $user = AdminAuth::getCurrentUser();
        if (!AdminAuth::userHasRole($user, AdminAuth::EVENT_MANAGEMENT_ROLES)) {
            Response::error('Insufficient permissions. Head admin, admin, or event manager role required.', 403);
            exit;
        }
    }

    /**
     * Get all locations for admin (including drafts)
     * GET /api/admin/stammtisch-locations
     */
    public static function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        self::requireLocationAdmin();

        try {
            $locations = StammtischLocation::getAllForAdmin();
            $locationsArray = array_map(fn($location) => $location->toArray(), $locations);
            Response::success($locationsArray, 'Stammtisch locations retrieved successfully');
        } catch (\Exception $e) {
            error_log("Error fetching admin stammtisch locations: " . $e->getMessage());
            Response::error('Failed to fetch stammtisch locations', 500);
        }
    }

    /**
     * Get specific location
     * GET /api/admin/stammtisch-locations/{id}
     */
    public static function show(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        self::requireLocationAdmin();

        try {
            $location = StammtischLocation::getById($id);
            if (!$location) {
                Response::notFound(['message' => 'Stammtisch location not found']);
                return;
            }

            Response::success($location->toArray(), 'Stammtisch location retrieved successfully');
        } catch (\Exception $e) {
            error_log("Error fetching stammtisch location: " . $e->getMessage());
            Response::error('Failed to fetch stammtisch location', 500);
        }
    }

    /**
     * Create new location
     * POST /api/admin/stammtisch-locations
     */
    public static function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        self::requireLocationAdmin();
        AdminAuth::requireCSRF();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        // Validate input
        $validator = new Validator($input);
        $validator->required(['name', 'city', 'region', 'country', 'latitude', 'longitude']);
        $validator->length('name', 3, 255);
        $validator->length('city', 2, 100);
        $validator->length('region', 2, 100);

        if (isset($input['contact_email']) && !empty($input['contact_email'])) {
            $validator->email('contact_email');
        }

        // Manual validation for fields not supported by the validator
        if (!Validator::isValidEnum($input['country'] ?? '', ['DE', 'AT', 'CH'])) {
            $validator->getErrors()['country'] = 'Country must be DE, AT, or CH';
        }

        if (!is_numeric($input['latitude'] ?? '')) {
            $validator->getErrors()['latitude'] = 'Latitude must be a valid number';
        }

        if (!is_numeric($input['longitude'] ?? '')) {
            $validator->getErrors()['longitude'] = 'Longitude must be a valid number';
        }

        if (
            !$validator->isValid() ||
            !Validator::isValidEnum($input['country'] ?? '', ['DE', 'AT', 'CH']) ||
            !is_numeric($input['latitude'] ?? '') ||
            !is_numeric($input['longitude'] ?? '')
        ) {
            $errors = $validator->getErrors();
            if (!Validator::isValidEnum($input['country'] ?? '', ['DE', 'AT', 'CH'])) {
                $errors['country'] = 'Country must be DE, AT, or CH';
            }
            if (!is_numeric($input['latitude'] ?? '')) {
                $errors['latitude'] = 'Latitude must be a valid number';
            }
            if (!is_numeric($input['longitude'] ?? '')) {
                $errors['longitude'] = 'Longitude must be a valid number';
            }

            Response::error('Validation failed', 400, $errors);
            return;
        }

        try {
            $currentUser = AdminAuth::getCurrentUser();

            // Helper to convert empty strings to null for optional fields
            $emptyToNull = fn($value) => ($value === '' || $value === null) ? null : $value;

            // Create location object
            $location = new StammtischLocation(
                name: $input['name'],
                city: $input['city'],
                region: $input['region'],
                country: $input['country'],
                latitude: (float)$input['latitude'],
                longitude: (float)$input['longitude'],
                description: $input['description'] ?? '',
                contactEmail: $emptyToNull($input['contact_email'] ?? null),
                contactPhone: $emptyToNull($input['contact_phone'] ?? null),
                contactFetLife: $emptyToNull($input['contact_fetlife'] ?? null),
                contactWebsite: $emptyToNull($input['contact_website'] ?? null),
                meetingFrequency: $emptyToNull($input['meeting_frequency'] ?? null),
                meetingLocation: $emptyToNull($input['meeting_location'] ?? null),
                meetingAddress: $emptyToNull($input['meeting_address'] ?? null),
                nextMeeting: $emptyToNull($input['next_meeting'] ?? null),
                tags: $input['tags'] ?? [],
                isActive: $input['is_active'] ?? true,
                status: $input['status'] ?? 'draft',
                createdBy: $currentUser['id']
            );

            $locationId = $location->create();

            if ($locationId) {
                $createdLocation = StammtischLocation::getById($locationId);
                Response::success($createdLocation->toArray(), 'Stammtisch location created successfully', 201);
            } else {
                Response::error('Failed to create stammtisch location', 500);
            }
        } catch (\Exception $e) {
            error_log("Error creating stammtisch location: " . $e->getMessage());
            Response::error('Failed to create stammtisch location', 500);
        }
    }

    /**
     * Update location
     * PUT /api/admin/stammtisch-locations/{id}
     */
    public static function update(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            Response::error('Method not allowed', 405);
            return;
        }

        self::requireLocationAdmin();
        AdminAuth::requireCSRF();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        // Check if location exists
        $location = StammtischLocation::getById($id);
        if (!$location) {
            Response::notFound(['message' => 'Stammtisch location not found']);
            return;
        }

        // Validate input
        $validator = new Validator($input);

        // Only validate provided fields
        if (isset($input['name'])) {
            $validator->length('name', 3, 255);
        }
        if (isset($input['city'])) {
            $validator->length('city', 2, 100);
        }
        if (isset($input['region'])) {
            $validator->length('region', 2, 100);
        }
        if (isset($input['contact_email']) && !empty($input['contact_email'])) {
            $validator->email('contact_email');
        }

        // Manual validation for fields not supported by the validator
        $errors = $validator->getErrors();

        if (isset($input['country']) && !Validator::isValidEnum($input['country'], ['DE', 'AT', 'CH'])) {
            $errors['country'] = 'Country must be DE, AT, or CH';
        }
        if (isset($input['latitude']) && !is_numeric($input['latitude'])) {
            $errors['latitude'] = 'Latitude must be a valid number';
        }
        if (isset($input['longitude']) && !is_numeric($input['longitude'])) {
            $errors['longitude'] = 'Longitude must be a valid number';
        }

        if (!$validator->isValid() || !empty($errors)) {
            Response::error('Validation failed', 400, array_merge($validator->getErrors(), $errors));
            return;
        }

        try {
            // Update location properties
            if (isset($input['name'])) {
                $location->name = $input['name'];
            }
            if (isset($input['city'])) {
                $location->city = $input['city'];
            }
            if (isset($input['region'])) {
                $location->region = $input['region'];
            }
            if (isset($input['country'])) {
                $location->country = $input['country'];
            }
            if (isset($input['latitude'])) {
                $location->latitude = (float)$input['latitude'];
            }
            if (isset($input['longitude'])) {
                $location->longitude = (float)$input['longitude'];
            }
            if (isset($input['description'])) {
                $location->description = $input['description'];
            }
            if (isset($input['contact_email'])) {
                $location->contactEmail = $input['contact_email'] ?: null;
            }
            if (isset($input['contact_phone'])) {
                $location->contactPhone = $input['contact_phone'] ?: null;
            }
            if (isset($input['contact_fetlife'])) {
                $location->contactFetLife = $input['contact_fetlife'] ?: null;
            }
            if (isset($input['contact_website'])) {
                $location->contactWebsite = $input['contact_website'] ?: null;
            }
            if (isset($input['meeting_frequency'])) {
                $location->meetingFrequency = $input['meeting_frequency'] ?: null;
            }
            if (isset($input['meeting_location'])) {
                $location->meetingLocation = $input['meeting_location'] ?: null;
            }
            if (isset($input['meeting_address'])) {
                $location->meetingAddress = $input['meeting_address'] ?: null;
            }
            if (isset($input['next_meeting'])) {
                $location->nextMeeting = $input['next_meeting'] ?: null;
            }
            if (isset($input['tags'])) {
                $location->tags = $input['tags'];
            }
            if (isset($input['is_active'])) {
                $location->isActive = (bool)$input['is_active'];
            }
            if (isset($input['status'])) {
                $location->status = $input['status'];
            }

            $success = $location->update();

            if ($success) {
                $updatedLocation = StammtischLocation::getById($id);
                Response::success($updatedLocation->toArray(), 'Stammtisch location updated successfully');
            } else {
                Response::error('Failed to update stammtisch location', 500);
            }
        } catch (\Exception $e) {
            error_log("Error updating stammtisch location: " . $e->getMessage());
            Response::error('Failed to update stammtisch location', 500);
        }
    }

    /**
     * Delete location
     * DELETE /api/admin/stammtisch-locations/{id}
     */
    public static function delete(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            Response::error('Method not allowed', 405);
            return;
        }

        self::requireLocationAdmin();
        AdminAuth::requireCSRF();

        try {
            $location = StammtischLocation::getById($id);
            if (!$location) {
                Response::notFound(['message' => 'Stammtisch location not found']);
                return;
            }

            $success = $location->delete();

            if ($success) {
                Response::success(null, 'Stammtisch location deleted successfully');
            } else {
                Response::error('Failed to delete stammtisch location', 500);
            }
        } catch (\Exception $e) {
            error_log("Error deleting stammtisch location: " . $e->getMessage());
            Response::error('Failed to delete stammtisch location', 500);
        }
    }

    /**
     * Bulk publish/unpublish locations
     * POST /api/admin/stammtisch-locations/bulk-status
     */
    public static function bulkUpdateStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        self::requireLocationAdmin();
        AdminAuth::requireCSRF();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = new Validator($input);
        $validator->required(['ids', 'status']);

        if (!$validator->isValid() || !Validator::isValidEnum($input['status'] ?? '', ['draft', 'published', 'archived'])) {
            $errors = $validator->getErrors();
            if (!Validator::isValidEnum($input['status'] ?? '', ['draft', 'published', 'archived'])) {
                $errors['status'] = 'Status must be draft, published, or archived';
            }
            Response::error('Validation failed', 400, $errors);
            return;
        }

        try {
            $ids = $input['ids'];
            $status = $input['status'];

            if (empty($ids) || !is_array($ids)) {
                Response::error('Invalid IDs provided', 400);
                return;
            }

            // Update all locations
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $sql = "UPDATE stammtisch_locations SET status = ? WHERE id IN ($placeholders)";
            $params = array_merge([$status], $ids);

            Database::execute($sql, $params);

            Response::success(null, "Successfully updated {$status} status for " . count($ids) . " locations");
        } catch (\Exception $e) {
            error_log("Error bulk updating stammtisch locations: " . $e->getMessage());
            Response::error('Failed to update locations', 500);
        }
    }

    /**
     * Get location statistics
     * GET /api/admin/stammtisch-locations/stats
     */
    public static function stats(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        self::requireLocationAdmin();

        try {
            $stats = [
                'total' => 0,
                'published' => 0,
                'draft' => 0,
                'archived' => 0,
                'active' => 0,
                'inactive' => 0,
                'by_country' => [],
                'by_region' => []
            ];

            // Get overall stats
            $overallStats = Database::fetchAll("
        SELECT
          COUNT(*) as total,
          SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
          SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
          SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived,
          SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
          SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
        FROM stammtisch_locations
      ");

            if (!empty($overallStats)) {
                $stats = array_merge($stats, $overallStats[0]);
            }

            // Get stats by country
            $countryStats = Database::fetchAll("
        SELECT country, COUNT(*) as count
        FROM stammtisch_locations
        GROUP BY country
        ORDER BY count DESC
      ");
            $stats['by_country'] = $countryStats;

            // Get stats by region (top 10)
            $regionStats = Database::fetchAll("
        SELECT region, country, COUNT(*) as count
        FROM stammtisch_locations
        GROUP BY region, country
        ORDER BY count DESC
        LIMIT 10
      ");
            $stats['by_region'] = $regionStats;

            Response::success($stats, 'Location statistics retrieved successfully');
        } catch (\Exception $e) {
            error_log("Error fetching location stats: " . $e->getMessage());
            Response::error('Failed to fetch location statistics', 500);
        }
    }
}
