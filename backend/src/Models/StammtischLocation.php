<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Models;

use HypnoseStammtisch\Database\Database;

/**
 * StammtischLocation model for managing map locations
 */
class StammtischLocation
{
    public function __construct(
        public ?string $id = null,
        public string $name = '',
        public string $slug = '',
        public string $city = '',
        public string $region = '',
        public string $country = '',
        public float $latitude = 0.0,
        public float $longitude = 0.0,
        public string $description = '',
        public ?string $contactEmail = null,
        public ?string $contactPhone = null,
        public ?string $contactFetLife = null,
        public ?string $contactWebsite = null,
        public ?string $meetingFrequency = null,
        public ?string $meetingLocation = null,
        public ?string $meetingAddress = null,
        public ?string $nextMeeting = null,
        public array $tags = [],
        public bool $isActive = true,
        public string $status = 'draft',
        public ?int $createdBy = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $createdByUsername = null
    ) {
    }

    /**
     * Get all published and active locations
     */
    public static function getAllPublished(array $filters = []): array
    {
        try {
            $where = ['status = ?', 'is_active = ?'];
            $params = ['published', true];

            // Apply filters
            if (!empty($filters['countries'])) {
                $placeholders = str_repeat('?,', count($filters['countries']) - 1) . '?';
                $where[] = "country IN ($placeholders)";
                $params = array_merge($params, $filters['countries']);
            }

            if (!empty($filters['regions'])) {
                $placeholders = str_repeat('?,', count($filters['regions']) - 1) . '?';
                $where[] = "region IN ($placeholders)";
                $params = array_merge($params, $filters['regions']);
            }

            if (!empty($filters['tags'])) {
                foreach ($filters['tags'] as $tag) {
                    $where[] = "JSON_CONTAINS(tags, ?)";
                    $params[] = json_encode($tag);
                }
            }

            $whereClause = 'WHERE ' . implode(' AND ', $where);

            $sql = "SELECT * FROM stammtisch_locations $whereClause ORDER BY country, region, city, name";
            $results = Database::fetchAll($sql, $params);

            return array_map([self::class, 'fromArray'], $results);
        } catch (\Exception $e) {
            error_log('Error fetching stammtisch locations: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all locations for admin (including drafts)
     */
    public static function getAllForAdmin(): array
    {
        try {
            $sql = "SELECT l.*, u.username as created_by_username
                    FROM stammtisch_locations l
                    LEFT JOIN users u ON l.created_by = u.id
                    ORDER BY l.created_at DESC";
            $results = Database::fetchAll($sql);

            return array_map(function ($row) {
                $location = self::fromArray($row);
                $location->createdByUsername = $row['created_by_username'] ?? null;
                return $location;
            }, $results);
        } catch (\Exception $e) {
            error_log('Error fetching all stammtisch locations: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get location by ID
     */
    public static function getById(string $id): ?self
    {
        try {
            $sql = "SELECT * FROM stammtisch_locations WHERE id = ?";
            $data = Database::fetchOne($sql, [$id]);
            return $data ? self::fromArray($data) : null;
        } catch (\Exception $e) {
            error_log('Error fetching stammtisch location: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get location by slug
     */
    public static function getBySlug(string $slug): ?self
    {
        try {
            $sql = "SELECT * FROM stammtisch_locations WHERE slug = ?";
            $data = Database::fetchOne($sql, [$slug]);
            return $data ? self::fromArray($data) : null;
        } catch (\Exception $e) {
            error_log('Error fetching stammtisch location by slug: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create new location
     */
    public function create(): ?string
    {
        try {
            if (empty($this->id)) {
                $this->id = $this->generateId();
            }

            if (empty($this->slug)) {
                $this->slug = $this->generateSlug();
            }

            $sql = "INSERT INTO stammtisch_locations (
                id, name, slug, city, region, country, latitude, longitude,
                description, contact_email, contact_phone, contact_telegram, contact_website,
                meeting_frequency, meeting_location, meeting_address, next_meeting,
                tags, is_active, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            Database::execute($sql, [
                $this->id,
                $this->name,
                $this->slug,
                $this->city,
                $this->region,
                $this->country,
                $this->latitude,
                $this->longitude,
                $this->description,
                $this->contactEmail,
                $this->contactPhone,
                $this->contactFetLife,
                $this->contactWebsite,
                $this->meetingFrequency,
                $this->meetingLocation,
                $this->meetingAddress,
                $this->nextMeeting,
                json_encode($this->tags),
                $this->isActive,
                $this->status,
                $this->createdBy
            ]);

            return $this->id;
        } catch (\Exception $e) {
            error_log('Error creating stammtisch location: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update location
     */
    public function update(): bool
    {
        try {
            $sql = "UPDATE stammtisch_locations SET
                name = ?, slug = ?, city = ?, region = ?, country = ?,
                latitude = ?, longitude = ?, description = ?,
                contact_email = ?, contact_phone = ?, contact_telegram = ?, contact_website = ?,
                meeting_frequency = ?, meeting_location = ?, meeting_address = ?, next_meeting = ?,
                tags = ?, is_active = ?, status = ?
                WHERE id = ?";

            Database::execute($sql, [
                $this->name,
                $this->slug,
                $this->city,
                $this->region,
                $this->country,
                $this->latitude,
                $this->longitude,
                $this->description,
                $this->contactEmail,
                $this->contactPhone,
                $this->contactFetLife,
                $this->contactWebsite,
                $this->meetingFrequency,
                $this->meetingLocation,
                $this->meetingAddress,
                $this->nextMeeting,
                json_encode($this->tags),
                $this->isActive,
                $this->status,
                $this->id
            ]);

            return true;
        } catch (\Exception $e) {
            error_log('Error updating stammtisch location: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete location
     */
    public function delete(): bool
    {
        try {
            $sql = "DELETE FROM stammtisch_locations WHERE id = ?";
            Database::execute($sql, [$this->id]);
            return true;
        } catch (\Exception $e) {
            error_log('Error deleting stammtisch location: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get available regions for given countries
     */
    public static function getAvailableRegions(array $countries = []): array
    {
        try {
            $where = 'status = ? AND is_active = ?';
            $params = ['published', true];

            if (!empty($countries)) {
                $placeholders = str_repeat('?,', count($countries) - 1) . '?';
                $where .= " AND country IN ($placeholders)";
                $params = array_merge($params, $countries);
            }

            $sql = "SELECT DISTINCT region FROM stammtisch_locations WHERE $where ORDER BY region";
            $results = Database::fetchAll($sql, $params);

            return array_column($results, 'region');
        } catch (\Exception $e) {
            error_log('Error fetching available regions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available tags
     */
    public static function getAvailableTags(): array
    {
        try {
            $sql = "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(tags, CONCAT('$[', numbers.n, ']'))) as tag
                    FROM stammtisch_locations
                    CROSS JOIN (
                        SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                    ) numbers
                    WHERE status = ? AND is_active = ?
                    AND JSON_EXTRACT(tags, CONCAT('$[', numbers.n, ']')) IS NOT NULL
                    ORDER BY tag";

            $results = Database::fetchAll($sql, ['published', true]);
            return array_column($results, 'tag');
        } catch (\Exception $e) {
            // Fallback for simpler MySQL versions
            try {
                $sql = "SELECT tags FROM stammtisch_locations WHERE status = ? AND is_active = ?";
                $results = Database::fetchAll($sql, ['published', true]);

                $allTags = [];
                foreach ($results as $row) {
                    $tags = json_decode($row['tags'], true);
                    if (is_array($tags)) {
                        $allTags = array_merge($allTags, $tags);
                    }
                }

                return array_unique($allTags);
            } catch (\Exception $e2) {
                error_log('Error fetching available tags: ' . $e2->getMessage());
                return [];
            }
        }
    }

    /**
     * Convert array to StammtischLocation object
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? '',
            slug: $data['slug'] ?? '',
            city: $data['city'] ?? '',
            region: $data['region'] ?? '',
            country: $data['country'] ?? '',
            latitude: (float)($data['latitude'] ?? 0.0),
            longitude: (float)($data['longitude'] ?? 0.0),
            description: $data['description'] ?? '',
            contactEmail: $data['contact_email'] ?? null,
            contactPhone: $data['contact_phone'] ?? null,
            contactFetLife: $data['contact_telegram'] ?? null,
            contactWebsite: $data['contact_website'] ?? null,
            meetingFrequency: $data['meeting_frequency'] ?? null,
            meetingLocation: $data['meeting_location'] ?? null,
            meetingAddress: $data['meeting_address'] ?? null,
            nextMeeting: $data['next_meeting'] ?? null,
            tags: json_decode($data['tags'] ?? '[]', true) ?? [],
            isActive: (bool)($data['is_active'] ?? true),
            status: $data['status'] ?? 'draft',
            createdBy: $data['created_by'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null
        );
    }

    /**
     * Convert to array format
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'city' => $this->city,
            'region' => $this->region,
            'country' => $this->country,
            'coordinates' => [
                'lat' => $this->latitude,
                'lng' => $this->longitude
            ],
            'description' => $this->description,
            'contact' => [
                'email' => $this->contactEmail,
                'phone' => $this->contactPhone,
                'fetlife' => $this->contactFetLife,
                'website' => $this->contactWebsite
            ],
            'meetingInfo' => [
                'frequency' => $this->meetingFrequency,
                'location' => $this->meetingLocation,
                'address' => $this->meetingAddress,
                'nextMeeting' => $this->nextMeeting
            ],
            'tags' => $this->tags,
            'isActive' => $this->isActive,
            'status' => $this->status,
            'lastUpdated' => $this->updatedAt
        ];
    }

    /**
     * Generate UUID
     */
    private function generateId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Generate slug from name
     */
    private function generateSlug(): string
    {
        $slug = strtolower($this->name);
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;

        while (self::getBySlug($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
