<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Models\StammtischLocation;
use HypnoseStammtisch\Utils\Response;

/**
 * API Controller for stammtisch locations (map markers)
 */
class StammtischLocationController
{
  /**
   * GET /api/stammtisch-locations
   * Get all published stammtisch locations with optional filters
   */
  public static function getPublished(): void
  {
    try {
      $filters = [];

      // Parse filter parameters
      if (isset($_GET['countries'])) {
        $countries = array_filter(explode(',', $_GET['countries']));
        if (!empty($countries)) {
          $filters['countries'] = $countries;
        }
      }

      if (isset($_GET['regions'])) {
        $regions = array_filter(explode(',', $_GET['regions']));
        if (!empty($regions)) {
          $filters['regions'] = $regions;
        }
      }

      if (isset($_GET['tags'])) {
        $tags = array_filter(explode(',', $_GET['tags']));
        if (!empty($tags)) {
          $filters['tags'] = $tags;
        }
      }

      $locations = StammtischLocation::getAllPublished($filters);
      $data = array_map(fn($location) => $location->toArray(), $locations);

      Response::json(['success' => true, 'data' => $data]);
    } catch (\Exception $e) {
      error_log('Error fetching stammtisch locations: ' . $e->getMessage());
      Response::json(['success' => false, 'error' => 'Fehler beim Laden der Stammtisch-Standorte'], 500);
    }
  }

  /**
   * GET /api/stammtisch-locations/meta
   * Get metadata for filters (available regions, tags, etc.)
   */
  public static function getMeta(): void
  {
    try {
      $countries = $_GET['countries'] ?? '';
      $countryFilter = $countries ? array_filter(explode(',', $countries)) : [];

      $availableRegions = StammtischLocation::getAvailableRegions($countryFilter);
      $availableTags = StammtischLocation::getAvailableTags();

      Response::json([
        'success' => true,
        'data' => [
          'regions' => $availableRegions,
          'tags' => $availableTags,
          'countries' => [
            ['code' => 'DE', 'name' => 'Deutschland', 'flag' => '🇩🇪'],
            ['code' => 'AT', 'name' => 'Österreich', 'flag' => '🇦🇹'],
            ['code' => 'CH', 'name' => 'Schweiz', 'flag' => '🇨🇭']
          ]
        ]
      ]);
    } catch (\Exception $e) {
      error_log('Error fetching stammtisch locations meta: ' . $e->getMessage());
      Response::json(['success' => false, 'error' => 'Fehler beim Laden der Metadaten'], 500);
    }
  }

  /**
   * GET /api/stammtisch-locations/{id}
   * Get single stammtisch location by ID
   */
  public static function getById(string $id): void
  {
    try {
      $location = StammtischLocation::getById($id);

      if (!$location) {
        Response::json(['success' => false, 'error' => 'Stammtisch-Standort nicht gefunden'], 404);
        return;
      }

      if ($location->status !== 'published' || !$location->isActive) {
        Response::json(['success' => false, 'error' => 'Stammtisch-Standort nicht verfügbar'], 404);
        return;
      }

      Response::json(['success' => true, 'data' => $location->toArray()]);
    } catch (\Exception $e) {
      error_log('Error fetching stammtisch location: ' . $e->getMessage());
      Response::json(['success' => false, 'error' => 'Fehler beim Laden des Stammtisch-Standorts'], 500);
    }
  }
}
