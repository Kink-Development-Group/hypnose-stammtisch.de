<?php

declare(strict_types=1);

/**
 * Migration script to transfer hardcoded map data to database
 * Run this script to populate the database with existing stammtisch locations
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Models\StammtischLocation;

// Load configuration
Config::load(__DIR__ . '/..');

echo "Migrating hardcoded stammtisch locations to database...\n";

// Hardcoded data from src/stores/map.ts
$sampleLocations = [
  [
    'id' => 'berlin-01',
    'name' => 'Hypnose Stammtisch Berlin',
    'city' => 'Berlin',
    'region' => 'Berlin',
    'country' => 'DE',
    'latitude' => 52.52,
    'longitude' => 13.405,
    'description' => 'Monatlicher Stammtisch für alle Interessierten der Hypnose in Berlin. Sowohl Anfänger als auch erfahrene Hypnotiseure sind willkommen.',
    'contact_email' => 'berlin@hypnose-stammtisch.de',
    'contact_telegram' => '@HypnoseBerlin',
    'meeting_frequency' => 'Jeden 1. Samstag im Monat',
    'meeting_location' => 'Kulturzentrum Mitte, Seminarraum 3',
    'next_meeting' => '2025-10-04T18:00:00',
    'tags' => ['anfängerfreundlich', 'praxis', 'theorie'],
    'is_active' => true,
    'status' => 'published'
  ],
  [
    'id' => 'muenchen-01',
    'name' => 'München Hypnose Circle',
    'city' => 'München',
    'region' => 'Bayern',
    'country' => 'DE',
    'latitude' => 48.1351,
    'longitude' => 11.582,
    'description' => 'Praxisorientierte Treffen mit Fokus auf moderne Hypnosetechniken und Sicherheitspraktiken.',
    'contact_email' => 'muenchen@hypnose-stammtisch.de',
    'contact_website' => 'https://hypnose-muenchen.example.com',
    'meeting_frequency' => 'Jeden 3. Freitag im Monat',
    'meeting_location' => 'Volkshochschule München, Raum 204',
    'tags' => ['erfahren', 'praxis', 'sicherheit'],
    'is_active' => true,
    'status' => 'published'
  ],
  [
    'id' => 'hamburg-01',
    'name' => 'Hypnose Hamburg Nord',
    'city' => 'Hamburg',
    'region' => 'Hamburg',
    'country' => 'DE',
    'latitude' => 53.5511,
    'longitude' => 9.9937,
    'description' => 'Entspannte Atmosphäre für Austausch und praktische Übungen in der Hansestadt.',
    'contact_email' => 'hamburg@hypnose-stammtisch.de',
    'contact_telegram' => '@HypnoseHamburg',
    'meeting_frequency' => 'Jeden 2. Sonntag im Monat',
    'meeting_location' => 'Bürgerhaus Barmbek, Gruppraum A',
    'tags' => ['entspannt', 'praxis', 'austausch'],
    'is_active' => true,
    'status' => 'published'
  ],
  [
    'id' => 'koeln-01',
    'name' => 'Kölner Hypnose Runde',
    'city' => 'Köln',
    'region' => 'Nordrhein-Westfalen',
    'country' => 'DE',
    'latitude' => 50.9375,
    'longitude' => 6.9603,
    'description' => 'Gemeinschaftliches Lernen und Experimentieren mit verschiedenen Hypnosetechniken.',
    'contact_email' => 'koeln@hypnose-stammtisch.de',
    'meeting_frequency' => 'Jeden 1. Mittwoch im Monat',
    'meeting_location' => 'VHS Köln, Raum B12',
    'tags' => ['experimentell', 'gemeinschaft', 'lernen'],
    'is_active' => true,
    'status' => 'published'
  ],
  [
    'id' => 'wien-01',
    'name' => 'Wiener Hypnose Zirkel',
    'city' => 'Wien',
    'region' => 'Wien',
    'country' => 'AT',
    'latitude' => 48.2082,
    'longitude' => 16.3738,
    'description' => 'Traditioneller Stammtisch in Wien mit Fokus auf klassische und moderne Hypnose-Methoden.',
    'contact_email' => 'wien@hypnose-stammtisch.de',
    'meeting_frequency' => 'Jeden 2. Dienstag im Monat',
    'meeting_location' => 'Wiener Volkshochschule, Salon 5',
    'tags' => ['traditionell', 'klassisch', 'modern'],
    'is_active' => true,
    'status' => 'published'
  ],
  [
    'id' => 'graz-01',
    'name' => 'Grazer Hypnose Gruppe',
    'city' => 'Graz',
    'region' => 'Steiermark',
    'country' => 'AT',
    'latitude' => 47.0707,
    'longitude' => 15.4395,
    'description' => 'Kleine, aber feine Gruppe von Hypnose-Enthusiasten in der Steiermark.',
    'contact_email' => 'graz@hypnose-stammtisch.de',
    'meeting_frequency' => 'Jeden 3. Samstag im Monat',
    'meeting_location' => 'Kulturhaus Graz, Konferenzraum',
    'tags' => ['klein', 'enthusiasten', 'steiermark'],
    'is_active' => true,
    'status' => 'published'
  ],
  [
    'id' => 'zuerich-01',
    'name' => 'Zürcher Hypnose Kollektiv',
    'city' => 'Zürich',
    'region' => 'Zürich',
    'country' => 'CH',
    'latitude' => 47.3769,
    'longitude' => 8.5417,
    'description' => 'Progressive Hypnose-Community mit Fokus auf innovative Techniken und Forschung.',
    'contact_email' => 'zuerich@hypnose-stammtisch.de',
    'contact_website' => 'https://hypnose-zuerich.example.com',
    'meeting_frequency' => 'Jeden 1. Donnerstag im Monat',
    'meeting_location' => 'ETH Zürich, Nebengebäude, Raum 3.14',
    'tags' => ['progressiv', 'innovation', 'forschung'],
    'is_active' => true,
    'status' => 'published'
  ],
  [
    'id' => 'bern-01',
    'name' => 'Berner Hypnose Akademie',
    'city' => 'Bern',
    'region' => 'Bern',
    'country' => 'CH',
    'latitude' => 46.9481,
    'longitude' => 7.4474,
    'description' => 'Wissenschaftlich orientierte Gruppe mit Fokus auf Forschung und praktische Anwendung.',
    'contact_email' => 'bern@hypnose-stammtisch.de',
    'meeting_frequency' => 'Jeden 4. Donnerstag im Monat',
    'meeting_location' => 'Universität Bern, Gebäude 3, Raum 201',
    'tags' => ['wissenschaftlich', 'forschung', 'akademisch'],
    'is_active' => true,
    'status' => 'published'
  ]
];

$migratedCount = 0;
$skippedCount = 0;

foreach ($sampleLocations as $locationData) {
  try {
    // Check if location already exists
    $existing = StammtischLocation::getById($locationData['id']);
    if ($existing) {
      echo "Skipping existing location: {$locationData['name']}\n";
      $skippedCount++;
      continue;
    }

    // Create new location
    $location = new StammtischLocation(
      id: $locationData['id'],
      name: $locationData['name'],
      city: $locationData['city'],
      region: $locationData['region'],
      country: $locationData['country'],
      latitude: (float)$locationData['latitude'],
      longitude: (float)$locationData['longitude'],
      description: $locationData['description'],
      contactEmail: $locationData['contact_email'] ?? null,
      contactPhone: null,
      contactTelegram: $locationData['contact_telegram'] ?? null,
      contactWebsite: $locationData['contact_website'] ?? null,
      meetingFrequency: $locationData['meeting_frequency'] ?? null,
      meetingLocation: $locationData['meeting_location'] ?? null,
      meetingAddress: null,
      nextMeeting: isset($locationData['next_meeting']) ? $locationData['next_meeting'] : null,
      tags: $locationData['tags'] ?? [],
      isActive: $locationData['is_active'] ?? true,
      status: $locationData['status'] ?? 'published',
      createdBy: null // No specific admin user for migrated data
    );

    $result = $location->create();
    if ($result) {
      echo "✓ Migrated: {$locationData['name']} ({$locationData['city']})\n";
      $migratedCount++;
    } else {
      echo "✗ Failed to migrate: {$locationData['name']}\n";
    }
  } catch (Exception $e) {
    echo "✗ Error migrating {$locationData['name']}: " . $e->getMessage() . "\n";
  }
}

echo "\nMigration completed!\n";
echo "Migrated: $migratedCount locations\n";
echo "Skipped: $skippedCount locations (already existed)\n";

if ($migratedCount > 0) {
  echo "\n🎉 Success! The stammtisch locations have been migrated to the database.\n";
  echo "You can now remove the hardcoded data from src/stores/map.ts and switch to API-based loading.\n";
} else {
  echo "\nNo new locations were migrated.\n";
}
