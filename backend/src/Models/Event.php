<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Models;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\MockData;
use Carbon\Carbon;

/**
 * Event model for managing calendar events
 */
class Event
{
    public function __construct(
        public ?int $id = null,
        public string $title = '',
        public string $slug = '',
        public string $description = '',
        public string $content = '',
        public string $startDatetime = '',
        public string $endDatetime = '',
        public string $timezone = 'Europe/Berlin',
        public bool $isRecurring = false,
        public ?string $rrule = null,
        public ?string $recurrenceEndDate = null,
        public string $locationType = 'physical',
        public string $locationName = '',
        public string $locationAddress = '',
        public string $locationUrl = '',
        public string $locationInstructions = '',
        public string $category = 'stammtisch',
        public string $difficultyLevel = 'all',
        public ?int $maxParticipants = null,
        public int $currentParticipants = 0,
        public int $ageRestriction = 18,
        public string $requirements = '',
        public string $safetyNotes = '',
        public string $preparationNotes = '',
        public string $status = 'draft',
        public bool $isFeatured = false,
        public bool $requiresRegistration = true,
        public ?string $registrationDeadline = null,
        public string $organizerName = '',
        public string $organizerEmail = '',
        public string $organizerBio = '',
        public array $tags = [],
        public string $metaDescription = '',
        public string $imageUrl = '',
        public ?string $createdAt = null,
        public ?string $updatedAt = null
    ) {}

    /**
     * Get all published events
     */
    public static function getAllPublished(array $filters = []): array
    {
        try {
            $sql = "SELECT * FROM events WHERE status = 'published'";
            $params = [];

            // Add filters
            if (!empty($filters['category'])) {
                $sql .= " AND category = ?";
                $params[] = $filters['category'];
            }

            if (!empty($filters['difficulty_level'])) {
                $sql .= " AND difficulty_level = ?";
                $params[] = $filters['difficulty_level'];
            }

            if (!empty($filters['location_type'])) {
                $sql .= " AND location_type = ?";
                $params[] = $filters['location_type'];
            }

            if (!empty($filters['from_date'])) {
                $sql .= " AND start_datetime >= ?";
                $params[] = $filters['from_date'];
            }

            if (!empty($filters['to_date'])) {
                $sql .= " AND start_datetime <= ?";
                $params[] = $filters['to_date'];
            }

            $sql .= " ORDER BY start_datetime ASC";

            if (!empty($filters['limit'])) {
                $sql .= " LIMIT " . (int)$filters['limit'];
            }

            $rows = Database::fetchAll($sql, $params);

            return array_map([self::class, 'fromArray'], $rows);

        } catch (\Exception $e) {
            // Fallback to mock data if database is not available
            error_log("Database error in getAllPublished, using mock data: " . $e->getMessage());
            return MockData::getMockEventObjects($filters);
        }
    }

    /**
     * Get upcoming events
     */
    public static function getUpcoming(int $limit = 5): array
    {
        try {
            $sql = "SELECT * FROM events
                    WHERE status = 'published'
                    AND start_datetime > NOW()
                    ORDER BY start_datetime ASC
                    LIMIT ?";

            $rows = Database::fetchAll($sql, [$limit]);

            return array_map([self::class, 'fromArray'], $rows);

        } catch (\Exception $e) {
            error_log("Database error in getUpcoming, using mock data: " . $e->getMessage());

            $mockEvents = MockData::getUpcomingEvents($limit);
            return MockData::getMockEventObjects(['limit' => $limit, 'upcoming_only' => true]);
        }
    }

    /**
     * Get featured events
     */
    public static function getFeatured(int $limit = 3): array
    {
        try {
            $sql = "SELECT * FROM events
                    WHERE status = 'published'
                    AND is_featured = 1
                    AND start_datetime > NOW()
                    ORDER BY start_datetime ASC
                    LIMIT ?";

            $rows = Database::fetchAll($sql, [$limit]);

            return array_map([self::class, 'fromArray'], $rows);

        } catch (\Exception $e) {
            error_log("Database error in getFeatured, using mock data: " . $e->getMessage());

            $mockEvents = MockData::getFeaturedEvents($limit);
            return MockData::getMockEventObjects(['limit' => $limit, 'featured_only' => true]);
        }
    }

    /**
     * Find event by ID
     */
    public static function findById(int $id): ?self
    {
        $row = Database::fetchOne("SELECT * FROM events WHERE id = ?", [$id]);

        return $row ? self::fromArray($row) : null;
    }

    /**
     * Find event by slug
     */
    public static function findBySlug(string $slug): ?self
    {
        $row = Database::fetchOne("SELECT * FROM events WHERE slug = ?", [$slug]);

        return $row ? self::fromArray($row) : null;
    }

    /**
     * Save event to database
     */
    public function save(): bool
    {
        try {
            if ($this->id) {
                return $this->update();
            } else {
                return $this->insert();
            }
        } catch (\Exception $e) {
            error_log("Error saving event: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Insert new event
     */
    private function insert(): bool
    {
        $sql = "INSERT INTO events (
            title, slug, description, content, start_datetime, end_datetime, timezone,
            is_recurring, rrule, recurrence_end_date, location_type, location_name,
            location_address, location_url, location_instructions, category,
            difficulty_level, max_participants, age_restriction, requirements,
            safety_notes, preparation_notes, status, is_featured, requires_registration,
            registration_deadline, organizer_name, organizer_email, organizer_bio,
            tags, meta_description, image_url
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";

        $params = [
            $this->title, $this->slug, $this->description, $this->content,
            $this->startDatetime, $this->endDatetime, $this->timezone,
            $this->isRecurring, $this->rrule, $this->recurrenceEndDate,
            $this->locationType, $this->locationName, $this->locationAddress,
            $this->locationUrl, $this->locationInstructions, $this->category,
            $this->difficultyLevel, $this->maxParticipants, $this->ageRestriction,
            $this->requirements, $this->safetyNotes, $this->preparationNotes,
            $this->status, $this->isFeatured, $this->requiresRegistration,
            $this->registrationDeadline, $this->organizerName, $this->organizerEmail,
            $this->organizerBio, json_encode($this->tags), $this->metaDescription,
            $this->imageUrl
        ];

        $id = Database::insert($sql, $params);

        if ($id) {
            $this->id = (int)$id;
            return true;
        }

        return false;
    }

    /**
     * Update existing event
     */
    private function update(): bool
    {
        $sql = "UPDATE events SET
            title = ?, slug = ?, description = ?, content = ?, start_datetime = ?,
            end_datetime = ?, timezone = ?, is_recurring = ?, rrule = ?,
            recurrence_end_date = ?, location_type = ?, location_name = ?,
            location_address = ?, location_url = ?, location_instructions = ?,
            category = ?, difficulty_level = ?, max_participants = ?, age_restriction = ?,
            requirements = ?, safety_notes = ?, preparation_notes = ?, status = ?,
            is_featured = ?, requires_registration = ?, registration_deadline = ?,
            organizer_name = ?, organizer_email = ?, organizer_bio = ?, tags = ?,
            meta_description = ?, image_url = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";

        $params = [
            $this->title, $this->slug, $this->description, $this->content,
            $this->startDatetime, $this->endDatetime, $this->timezone,
            $this->isRecurring, $this->rrule, $this->recurrenceEndDate,
            $this->locationType, $this->locationName, $this->locationAddress,
            $this->locationUrl, $this->locationInstructions, $this->category,
            $this->difficultyLevel, $this->maxParticipants, $this->ageRestriction,
            $this->requirements, $this->safetyNotes, $this->preparationNotes,
            $this->status, $this->isFeatured, $this->requiresRegistration,
            $this->registrationDeadline, $this->organizerName, $this->organizerEmail,
            $this->organizerBio, json_encode($this->tags), $this->metaDescription,
            $this->imageUrl, $this->id
        ];

        Database::execute($sql, $params);

        return true;
    }

    /**
     * Delete event
     */
    public function delete(): bool
    {
        if (!$this->id) {
            return false;
        }

        Database::execute("DELETE FROM events WHERE id = ?", [$this->id]);

        return true;
    }

    /**
     * Create event from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            title: $data['title'] ?? '',
            slug: $data['slug'] ?? '',
            description: $data['description'] ?? '',
            content: $data['content'] ?? '',
            startDatetime: $data['start_datetime'] ?? '',
            endDatetime: $data['end_datetime'] ?? '',
            timezone: $data['timezone'] ?? 'Europe/Berlin',
            isRecurring: (bool)($data['is_recurring'] ?? false),
            rrule: $data['rrule'],
            recurrenceEndDate: $data['recurrence_end_date'],
            locationType: $data['location_type'] ?? 'physical',
            locationName: $data['location_name'] ?? '',
            locationAddress: $data['location_address'] ?? '',
            locationUrl: $data['location_url'] ?? '',
            locationInstructions: $data['location_instructions'] ?? '',
            category: $data['category'] ?? 'stammtisch',
            difficultyLevel: $data['difficulty_level'] ?? 'all',
            maxParticipants: $data['max_participants'],
            currentParticipants: $data['current_participants'] ?? 0,
            ageRestriction: $data['age_restriction'] ?? 18,
            requirements: $data['requirements'] ?? '',
            safetyNotes: $data['safety_notes'] ?? '',
            preparationNotes: $data['preparation_notes'] ?? '',
            status: $data['status'] ?? 'draft',
            isFeatured: (bool)($data['is_featured'] ?? false),
            requiresRegistration: (bool)($data['requires_registration'] ?? true),
            registrationDeadline: $data['registration_deadline'],
            organizerName: $data['organizer_name'] ?? '',
            organizerEmail: $data['organizer_email'] ?? '',
            organizerBio: $data['organizer_bio'] ?? '',
            tags: json_decode($data['tags'] ?? '[]', true),
            metaDescription: $data['meta_description'] ?? '',
            imageUrl: $data['image_url'] ?? '',
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'content' => $this->content,
            'start_datetime' => $this->startDatetime,
            'end_datetime' => $this->endDatetime,
            'timezone' => $this->timezone,
            'is_recurring' => $this->isRecurring,
            'rrule' => $this->rrule,
            'recurrence_end_date' => $this->recurrenceEndDate,
            'location_type' => $this->locationType,
            'location_name' => $this->locationName,
            'location_address' => $this->locationAddress,
            'location_url' => $this->locationUrl,
            'location_instructions' => $this->locationInstructions,
            'category' => $this->category,
            'difficulty_level' => $this->difficultyLevel,
            'max_participants' => $this->maxParticipants,
            'current_participants' => $this->currentParticipants,
            'age_restriction' => $this->ageRestriction,
            'requirements' => $this->requirements,
            'safety_notes' => $this->safetyNotes,
            'preparation_notes' => $this->preparationNotes,
            'status' => $this->status,
            'is_featured' => $this->isFeatured,
            'requires_registration' => $this->requiresRegistration,
            'registration_deadline' => $this->registrationDeadline,
            'organizer_name' => $this->organizerName,
            'organizer_email' => $this->organizerEmail,
            'organizer_bio' => $this->organizerBio,
            'tags' => $this->tags,
            'meta_description' => $this->metaDescription,
            'image_url' => $this->imageUrl,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    /**
     * Generate unique slug from title
     */
    public function generateSlug(): void
    {
        $baseSlug = $this->slugify($this->title);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $this->slug = $slug;
    }

    /**
     * Create URL-friendly slug
     */
    private function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[äöüß]/u', ['ae', 'oe', 'ue', 'ss'], $text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);

        return trim($text, '-');
    }

    /**
     * Check if slug already exists
     */
    private function slugExists(string $slug): bool
    {
        $sql = "SELECT id FROM events WHERE slug = ?";

        if ($this->id) {
            $sql .= " AND id != ?";
            $result = Database::fetchOne($sql, [$slug, $this->id]);
        } else {
            $result = Database::fetchOne($sql, [$slug]);
        }

        return $result !== false;
    }

    /**
     * Get all events including recurring instances for a date range
     */
    public static function getExpandedEvents(Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        // First get all base events
        $baseEvents = self::getAllPublished($filters);
        $expandedEvents = [];

        foreach ($baseEvents as $event) {
            if ($event->isRecurring && !empty($event->rrule)) {
                // Expand recurring event
                try {
                    $eventArray = $event->toArray();
                    $instances = \HypnoseStammtisch\Utils\RRuleProcessor::expandRecurringEvent(
                        $eventArray,
                        $startDate,
                        $endDate,
                        []
                    );

                    // Convert back to Event objects
                    foreach ($instances as $instance) {
                        $expandedEvents[] = self::fromArray($instance);
                    }
                } catch (\Exception $e) {
                    error_log("Error expanding recurring event {$event->id}: " . $e->getMessage());
                    // Fall back to base event
                    $eventStart = Carbon::parse($event->startDatetime);
                    if ($eventStart->between($startDate, $endDate)) {
                        $expandedEvents[] = $event;
                    }
                }
            } else {
                // Check if single event falls in date range
                $eventStart = Carbon::parse($event->startDatetime);
                if ($eventStart->between($startDate, $endDate)) {
                    $expandedEvents[] = $event;
                }
            }
        }

        // Sort by start date
        usort($expandedEvents, function($a, $b) {
            return strtotime($a->startDatetime) - strtotime($b->startDatetime);
        });

        return $expandedEvents;
    }

    /**
     * Create recurring event with RRULE
     */
    public static function createRecurring(array $data): self
    {
        // Validate RRULE if provided
        if (!empty($data['rrule'])) {
            $errors = \HypnoseStammtisch\Utils\RRuleProcessor::validateRRule($data['rrule']);
            if (!empty($errors)) {
                throw new \InvalidArgumentException('Invalid RRULE: ' . implode(', ', $errors));
            }
        }

        $data['is_recurring'] = true;
        $event = self::fromArray($data);
        $event->save();
        return $event;
    }

    /**
     * Add exception date to recurring event
     */
    public function addExceptionDate(string $date): bool
    {
        if (!$this->isRecurring) {
            throw new \InvalidArgumentException('Cannot add exception date to non-recurring event');
        }

        try {
            $exdates = $this->getExceptionDates();
            if (!in_array($date, $exdates)) {
                $exdates[] = $date;
                sort($exdates);

                return $this->updateExceptionDates($exdates);
            }
            return true;
        } catch (\Exception $e) {
            error_log("Error adding exception date: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove exception date from recurring event
     */
    public function removeExceptionDate(string $date): bool
    {
        if (!$this->isRecurring) {
            throw new \InvalidArgumentException('Cannot remove exception date from non-recurring event');
        }

        try {
            $exdates = $this->getExceptionDates();
            $index = array_search($date, $exdates);

            if ($index !== false) {
                unset($exdates[$index]);
                $exdates = array_values($exdates);

                return $this->updateExceptionDates($exdates);
            }
            return true;
        } catch (\Exception $e) {
            error_log("Error removing exception date: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get exception dates for recurring event
     */
    public function getExceptionDates(): array
    {
        if (!$this->isRecurring) {
            return [];
        }

        try {
            $sql = "SELECT exdates_utc FROM event_series WHERE id = (SELECT series_id FROM events WHERE id = ?)";
            $result = Database::fetchOne($sql, [$this->id]);

            if ($result && !empty($result['exdates_utc'])) {
                $exdates = json_decode($result['exdates_utc'], true);
                return is_array($exdates) ? $exdates : [];
            }

            return [];
        } catch (\Exception $e) {
            error_log("Error getting exception dates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update exception dates for recurring event
     */
    private function updateExceptionDates(array $exdates): bool
    {
        try {
            $sql = "UPDATE event_series SET exdates_utc = ? WHERE id = (SELECT series_id FROM events WHERE id = ?)";
            $result = Database::execute($sql, [json_encode($exdates), $this->id]);
            return $result !== false;
        } catch (\Exception $e) {
            error_log("Error updating exception dates: " . $e->getMessage());
            return false;
        }
    }
}
