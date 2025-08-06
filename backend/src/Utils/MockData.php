<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

/**
 * Mock data provider for development and testing
 */
class MockData
{
    /**
     * Get mock events data
     */
    public static function getEvents(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Hamburger Hypnose Munch - Hypnose 101',
                'slug' => 'hamburger-hypnose-munch-101',
                'description' => 'Monatlicher Einsteiger-Abend im Club Catonium. Lerne die Grundlagen der erotischen Hypnose in sicherer Atmosphäre. Keine Vorerfahrung nötig - alle Erfahrungsstufen willkommen.',
                'short_description' => 'Einsteiger-Workshop für erotische Hypnose',
                'start_date' => date('Y-m-d', strtotime('first friday of next month')),
                'start_time' => '19:30:00',
                'end_date' => date('Y-m-d', strtotime('first friday of next month')),
                'end_time' => '23:00:00',
                'location' => 'Club Catonium Hamburg',
                'address' => 'Bernhard-Nocht-Str. 89a, 20359 Hamburg',
                'price' => 10.00,
                'max_participants' => 30,
                'current_participants' => 18,
                'category' => 'stammtisch',
                'tags' => 'einsteiger,hamburg,erotisch,munch,beginner-friendly',
                'organizer_name' => 'JohnaSwitch',
                'organizer_email' => 'hamburg@hypnose-stammtisch.de',
                'organizer_phone' => null,
                'is_featured' => true,
                'is_published' => true,
                'requires_registration' => false,
                'registration_deadline' => null,
                'image_url' => 'https://example.com/images/hamburg-munch.jpg',
                'external_url' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-60 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ],
            [
                'id' => 2,
                'title' => 'Hypnose-Stammtisch Rhein-Main',
                'slug' => 'hypnose-stammtisch-rhein-main',
                'description' => 'Monatliches Treffen für Begeisterte der erotischen, BDSM- und Freizeithypnose. Austausch, Networking und praktische Übungen in vertrauensvoller Runde.',
                'short_description' => 'Monatlicher Stammtisch für erotische Hypnose',
                'start_date' => date('Y-m-d', strtotime('second saturday of next month')),
                'start_time' => '18:00:00',
                'end_date' => date('Y-m-d', strtotime('second saturday of next month')),
                'end_time' => '22:00:00',
                'location' => 'Eventlocation Mainz',
                'address' => 'Rheinstr. 45, 55116 Mainz',
                'price' => 15.00,
                'max_participants' => 25,
                'current_participants' => 12,
                'category' => 'stammtisch',
                'tags' => 'rhein-main,mainz,erotisch,bdsm,stammtisch',
                'organizer_name' => 'Hypnose Rhein-Main Team',
                'organizer_email' => 'rhein-main@hypnose-stammtisch.de',
                'organizer_phone' => null,
                'is_featured' => true,
                'is_published' => true,
                'requires_registration' => true,
                'registration_deadline' => date('Y-m-d', strtotime('second saturday of next month -3 days')),
                'image_url' => 'https://example.com/images/rhein-main.jpg',
                'external_url' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-45 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
                'description' => 'Lernen Sie, wie Hypnose bei der Bewältigung von Stress und Burnout helfen kann. Praktische Übungen und Selbsthypnose-Techniken stehen im Mittelpunkt.',
                'short_description' => 'Stressbewältigung mit Hypnose',
                'start_date' => date('Y-m-d', strtotime('+14 days')),
                'start_time' => '10:00:00',
                'end_date' => date('Y-m-d', strtotime('+14 days')),
                'end_time' => '16:00:00',
                'location' => 'Wellness Center Schwabing',
                'address' => 'Leopoldstr. 45, 80802 München',
                'price' => 85.00,
                'max_participants' => 15,
                'current_participants' => 12,
                'category' => 'seminar',
                'tags' => 'stress,burnout,gesundheit,selbsthypnose',
                'organizer_name' => 'Thomas Weber',
                'organizer_email' => 'thomas.weber@wellness-schwabing.de',
                'organizer_phone' => '+49 89 87654321',
                'is_featured' => false,
                'is_published' => true,
                'requires_registration' => true,
                'registration_deadline' => date('Y-m-d', strtotime('+12 days')),
                'image_url' => 'https://example.com/images/stress-management.jpg',
                'external_url' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-20 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'id' => 3,
                'title' => 'Hypno Study Frankfurt - Deeptalk',
                'slug' => 'hypno-study-frankfurt-deeptalk',
                'description' => 'Deeptalk-Stammtisch mit Fokus auf Techniken und Diskussionen. Englischsprachige Teilnehmer willkommen. Austausch zwischen erfahrenen Hypnotiseuren.',
                'short_description' => 'Technikaustausch für Fortgeschrittene',
                'start_date' => date('Y-m-d', strtotime('third wednesday of next month')),
                'start_time' => '19:00:00',
                'end_date' => date('Y-m-d', strtotime('third wednesday of next month')),
                'end_time' => '22:30:00',
                'location' => 'Eventspace Frankfurt',
                'address' => 'Mainzer Landstr. 123, 60327 Frankfurt am Main',
                'price' => 12.00,
                'max_participants' => 20,
                'current_participants' => 15,
                'category' => 'study-group',
                'tags' => 'frankfurt,fortgeschrittene,techniken,english-friendly',
                'organizer_name' => 'Hypno Study Frankfurt',
                'organizer_email' => 'frankfurt@hypnose-stammtisch.de',
                'organizer_phone' => null,
                'is_featured' => true,
                'is_published' => true,
                'requires_registration' => true,
                'registration_deadline' => date('Y-m-d', strtotime('third wednesday of next month -2 days')),
                'image_url' => 'https://example.com/images/frankfurt-study.jpg',
                'external_url' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'id' => 4,
                'title' => 'Hamburger Hypnose Munch - Koalabox Praxis',
                'slug' => 'hamburger-hypnose-munch-koalabox',
                'description' => 'Praxis-Abend im Club Catonium für alle mit Grundkenntnissen. Ausprobieren, Üben und Vertiefen von Hypnose-Techniken unter Anleitung.',
                'short_description' => 'Praxis-Session für Geübte',
                'start_date' => date('Y-m-d', strtotime('third friday of next month')),
                'start_time' => '20:00:00',
                'end_date' => date('Y-m-d', strtotime('third friday of next month')),
                'end_time' => '23:30:00',
                'location' => 'Club Catonium Hamburg',
                'address' => 'Bernhard-Nocht-Str. 89a, 20359 Hamburg',
                'price' => 10.00,
                'max_participants' => 25,
                'current_participants' => 16,
                'category' => 'practice',
                'tags' => 'hamburg,praxis,fortgeschrittene,erotisch,koalabox',
                'organizer_name' => 'JohnaSwitch',
                'organizer_email' => 'hamburg@hypnose-stammtisch.de',
                'organizer_phone' => null,
                'is_featured' => false,
                'is_published' => true,
                'requires_registration' => false,
                'registration_deadline' => null,
                'image_url' => 'https://example.com/images/koalabox.jpg',
                'external_url' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-45 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],
            [
                'id' => 5,
                'title' => 'Bremen Hypnose Meetup - Kennenlernen',
                'slug' => 'bremen-hypnose-meetup',
                'description' => 'Neues monatliches Meetup in Bremen für alle Interessierten der Freizeit- und erotischen Hypnose. Erste Schritte und Community-Aufbau.',
                'short_description' => 'Neues Meetup in Bremen',
                'start_date' => date('Y-m-d', strtotime('last saturday of next month')),
                'start_time' => '18:30:00',
                'end_date' => date('Y-m-d', strtotime('last saturday of next month')),
                'end_time' => '21:30:00',
                'location' => 'Kulturzentrum Bremen',
                'address' => 'Pieperstr. 16, 28195 Bremen',
                'price' => 8.00,
                'max_participants' => 15,
                'current_participants' => 7,
                'category' => 'meetup',
                'tags' => 'bremen,neu,kennenlernen,beginner-friendly,erotisch',
                'organizer_name' => 'Bremen Hypno Community',
                'organizer_email' => 'bremen@hypnose-stammtisch.de',
                'organizer_phone' => null,
                'is_featured' => true,
                'is_published' => true,
                'requires_registration' => true,
                'registration_deadline' => date('Y-m-d', strtotime('last saturday of next month -1 day')),
                'image_url' => 'https://example.com/images/bremen-meetup.jpg',
                'external_url' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-15 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'id' => 6,
                'title' => 'Online: Konsens & Sicherheit in der Hypnose',
                'slug' => 'online-konsens-sicherheit-hypnose',
                'description' => 'Webinar über die wichtigsten Sicherheitsaspekte und Konsens-Praktiken bei erotischer Hypnose. Für alle Erfahrungsstufen.',
                'short_description' => 'Sicherheits-Webinar für alle',
                'start_date' => date('Y-m-d', strtotime('+10 days')),
                'start_time' => '19:00:00',
                'end_date' => date('Y-m-d', strtotime('+10 days')),
                'end_time' => '21:00:00',
                'location' => 'Online (Zoom)',
                'address' => null,
                'price' => 0.00,
                'max_participants' => 50,
                'current_participants' => 23,
                'category' => 'webinar',
                'tags' => 'online,sicherheit,konsens,webinar,alle-stufen',
                'organizer_name' => 'Hypnose Stammtisch Team',
                'organizer_email' => 'info@hypnose-stammtisch.de',
                'organizer_phone' => null,
                'is_featured' => true,
                'is_published' => true,
                'requires_registration' => true,
                'registration_deadline' => date('Y-m-d', strtotime('+9 days')),
                'image_url' => 'https://example.com/images/safety-webinar.jpg',
                'external_url' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
                'is_featured' => false,
                'is_published' => true,
                'requires_registration' => true,
                'registration_deadline' => date('Y-m-d', strtotime('+19 days')),
                'image_url' => 'https://example.com/images/sleep-hypnosis.jpg',
                'external_url' => 'https://hypno-online.de/webinar/schlaf',
                'created_at' => date('Y-m-d H:i:s', strtotime('-15 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            [
                'id' => 5,
                'title' => 'Rauchentwöhnung mit Hypnose',
                'slug' => 'rauchentwohung-mit-hypnose',
                'description' => 'Ein intensives Seminar zur Rauchentwöhnung mit bewährten Hypnose-Methoden. Hohe Erfolgsquote und individuelle Betreuung.',
                'short_description' => 'Erfolgreich Nichtraucher werden',
                'start_date' => date('Y-m-d', strtotime('+28 days')),
                'start_time' => '09:00:00',
                'end_date' => date('Y-m-d', strtotime('+29 days')),
                'end_time' => '17:00:00',
                'location' => 'Praxis für Hypnosetherapie',
                'address' => 'Sendlinger Str. 8, 80331 München',
                'price' => 250.00,
                'max_participants' => 8,
                'current_participants' => 3,
                'category' => 'therapie',
                'tags' => 'rauchentwöhnung,therapie,gesundheit,sucht',
                'organizer_name' => 'Dr. Michael Hofmann',
                'organizer_email' => 'michael.hofmann@hypnotherapie-muenchen.de',
                'organizer_phone' => '+49 89 98765432',
                'is_featured' => true,
                'is_published' => true,
                'requires_registration' => true,
                'registration_deadline' => date('Y-m-d', strtotime('+25 days')),
                'image_url' => 'https://example.com/images/quit-smoking.jpg',
                'external_url' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))
            ]
        ];
    }

    /**
     * Get mock event categories
     */
    public static function getCategories(): array
    {
        return [
            'workshop' => 'Workshop',
            'seminar' => 'Seminar',
            'stammtisch' => 'Stammtisch',
            'webinar' => 'Webinar',
            'therapie' => 'Therapie',
            'ausbildung' => 'Ausbildung',
            'konferenz' => 'Konferenz'
        ];
    }

    /**
     * Get mock event tags
     */
    public static function getTags(): array
    {
        return [
            'einsteiger',
            'fortgeschritten',
            'profi',
            'grundlagen',
            'praxis',
            'theorie',
            'selbsthypnose',
            'therapie',
            'entspannung',
            'stress',
            'burnout',
            'schlaf',
            'rauchentwöhnung',
            'gewichtsreduktion',
            'ängste',
            'phobien',
            'schmerzen',
            'sport',
            'leistung',
            'münchen',
            'berlin',
            'hamburg',
            'köln',
            'online',
            'präsenz',
            'hybrid'
        ];
    }

    /**
     * Get upcoming events
     */
    public static function getUpcomingEvents(int $limit = 10): array
    {
        $events = self::getEvents();

        // Filter for upcoming events
        $upcoming = array_filter($events, function($event) {
            return strtotime($event['start_date']) >= strtotime('today');
        });

        // Sort by start date
        usort($upcoming, function($a, $b) {
            return strtotime($a['start_date']) - strtotime($b['start_date']);
        });

        return array_slice($upcoming, 0, $limit);
    }

    /**
     * Get featured events
     */
    public static function getFeaturedEvents(int $limit = 5): array
    {
        $events = self::getEvents();

        // Filter for featured and upcoming events
        $featured = array_filter($events, function($event) {
            return $event['is_featured'] &&
                   strtotime($event['start_date']) >= strtotime('today');
        });

        // Sort by start date
        usort($featured, function($a, $b) {
            return strtotime($a['start_date']) - strtotime($b['start_date']);
        });

        return array_slice($featured, 0, $limit);
    }

    /**
     * Get event by ID or slug
     */
    public static function getEventByIdOrSlug($identifier): ?array
    {
        $events = self::getEvents();

        foreach ($events as $event) {
            if ($event['id'] == $identifier || $event['slug'] === $identifier) {
                return $event;
            }
        }

        return null;
    }

    /**
     * Filter events by parameters
     */
    public static function filterEvents(array $params): array
    {
        $events = self::getEvents();

        // Filter for upcoming events only
        if (!empty($params['upcoming_only'])) {
            $events = array_filter($events, function($event) {
                return strtotime($event['start_date']) >= strtotime('today');
            });
        }

        // Filter for featured events only
        if (!empty($params['featured_only'])) {
            $events = array_filter($events, function($event) {
                return $event['is_featured'] &&
                       strtotime($event['start_date']) >= strtotime('today');
            });
        }

        // Filter by category
        if (!empty($params['category'])) {
            $events = array_filter($events, function($event) use ($params) {
                return $event['category'] === $params['category'];
            });
        }

        // Filter by tag
        if (!empty($params['tag'])) {
            $events = array_filter($events, function($event) use ($params) {
                $tags = explode(',', $event['tags']);
                return in_array($params['tag'], array_map('trim', $tags));
            });
        }

        // Filter by location (search in location and address)
        if (!empty($params['location'])) {
            $events = array_filter($events, function($event) use ($params) {
                $searchTerm = strtolower($params['location']);
                return strpos(strtolower($event['location']), $searchTerm) !== false ||
                       ($event['address'] && strpos(strtolower($event['address']), $searchTerm) !== false);
            });
        }

        // Filter by date range
        if (!empty($params['from'])) {
            $fromDate = $params['from'];
            $events = array_filter($events, function($event) use ($fromDate) {
                return $event['start_date'] >= $fromDate;
            });
        }

        if (!empty($params['to'])) {
            $toDate = $params['to'];
            $events = array_filter($events, function($event) use ($toDate) {
                return $event['start_date'] <= $toDate;
            });
        }

        // Alternative date filters (for compatibility with Event model)
        if (!empty($params['from_date'])) {
            $fromDate = $params['from_date'];
            $events = array_filter($events, function($event) use ($fromDate) {
                return ($event['start_date'] . ' ' . $event['start_time']) >= $fromDate;
            });
        }

        if (!empty($params['to_date'])) {
            $toDate = $params['to_date'];
            $events = array_filter($events, function($event) use ($toDate) {
                return ($event['start_date'] . ' ' . $event['start_time']) <= $toDate;
            });
        }

        // Sort by start date
        usort($events, function($a, $b) {
            return strtotime($a['start_date'] . ' ' . $a['start_time']) -
                   strtotime($b['start_date'] . ' ' . $b['start_time']);
        });

        // Apply limit
        if (!empty($params['limit'])) {
            $events = array_slice($events, 0, (int)$params['limit']);
        }

        return $events;
    }

    /**
     * Get mock Event objects (compatible with Event model)
     */
    public static function getMockEventObjects(array $filters = []): array
    {
        $mockData = self::filterEvents($filters);
        $events = [];

        foreach ($mockData as $data) {
            // Create a simple object that matches the toArray() output format
            $event = new \stdClass();
            $event->id = $data['id'];
            $event->title = $data['title'];
            $event->slug = $data['slug'];
            $event->description = $data['description'];
            $event->status = 'published';
            $event->category = $data['category'];
            $event->tags = $data['tags'];
            $event->start_datetime = $data['start_date'] . ' ' . $data['start_time'];
            $event->end_datetime = $data['end_date'] . ' ' . $data['end_time'];
            $event->location_type = strpos(strtolower($data['location']), 'online') !== false ? 'online' : 'in_person';
            $event->venue_name = $data['location'];
            $event->venue_address = $data['address'];
            $event->price = $data['price'];
            $event->currency = 'EUR';
            $event->max_participants = $data['max_participants'];
            $event->registration_required = $data['requires_registration'];
            $event->registration_deadline = $data['registration_deadline'];
            $event->organizer_name = $data['organizer_name'];
            $event->organizer_email = $data['organizer_email'];
            $event->organizer_phone = $data['organizer_phone'];
            $event->image_url = $data['image_url'];
            $event->external_url = $data['external_url'];
            $event->is_featured = $data['is_featured'];
            $event->difficulty_level = 'beginner';
            $event->language = 'de';
            $event->timezone = 'Europe/Berlin';
            $event->created_at = $data['created_at'];
            $event->updated_at = $data['updated_at'];

            // Add toArray method to the mock object
            $event->toArray = function() use ($data) {
                return [
                    'id' => $data['id'],
                    'title' => $data['title'],
                    'slug' => $data['slug'],
                    'description' => $data['description'],
                    'short_description' => $data['short_description'],
                    'status' => 'published',
                    'category' => $data['category'],
                    'tags' => explode(',', $data['tags']),
                    'start_datetime' => $data['start_date'] . ' ' . $data['start_time'],
                    'end_datetime' => $data['end_date'] . ' ' . $data['end_time'],
                    'location_type' => strpos(strtolower($data['location']), 'online') !== false ? 'online' : 'in_person',
                    'venue_name' => $data['location'],
                    'venue_address' => $data['address'],
                    'price' => $data['price'],
                    'currency' => 'EUR',
                    'max_participants' => $data['max_participants'],
                    'current_registrations' => $data['current_participants'],
                    'registration_required' => $data['requires_registration'],
                    'registration_deadline' => $data['registration_deadline'],
                    'organizer_name' => $data['organizer_name'],
                    'organizer_email' => $data['organizer_email'],
                    'organizer_phone' => $data['organizer_phone'],
                    'image_url' => $data['image_url'],
                    'external_url' => $data['external_url'],
                    'is_featured' => $data['is_featured'],
                    'difficulty_level' => 'beginner',
                    'language' => 'de',
                    'timezone' => 'Europe/Berlin',
                    'rrule' => $data['rrule'] ?? null,
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['updated_at']
                ];
            };

            $events[] = $event;
        }

        return $events;
    }
}
