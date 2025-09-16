<?php

declare(strict_types=1);

/**
 * Main API entry point for Hypnose Stammtisch backend
 *
 * This file handles all API requests and routes them to appropriate controllers.
 * It should be placed in the web root or configured with a web server.
 */

// Basis Error Reporting (Ausgabe später ggf. deaktiviert)
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Set timezone
date_default_timezone_set('Europe/Berlin');

// Include autoloader (will be created when composer install is run)
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode([
    'success' => false,
    'error' => 'Dependencies not installed. Please run: composer install'
  ]);
  exit;
}

require_once $autoloadPath;

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Controllers\EventsController;
use HypnoseStammtisch\Controllers\ContactController;
use HypnoseStammtisch\Controllers\CalendarController;
use HypnoseStammtisch\Controllers\FormController;
use HypnoseStammtisch\Controllers\StammtischLocationController;
use HypnoseStammtisch\Utils\Response;

// Load configuration
try {
  Config::load(dirname(__DIR__));
  // Fehlerausgabe nur im Debug Modus aktivieren
  if (Config::get('app.debug')) {
    ini_set('display_errors', '1');
  }
  // Security Headers (idempotent)
  header('X-Frame-Options: DENY');
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  header('X-XSS-Protection: 1; mode=block');
  header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
} catch (Exception $e) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode([
    'success' => false,
    'error' => 'Configuration error: ' . $e->getMessage()
  ]);
  exit;
}

// Handle CORS preflight requests
Response::handlePreflight();

// Enable CORS for all requests
Response::addCorsHeaders();

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '/';

// Remove query string from path
$path = parse_url($path, PHP_URL_PATH);

// Check if this is an admin API request (support both /api/admin/* and /admin/* depending on server docroot/rewrite)
if (str_starts_with($path, '/api/admin') || str_starts_with($path, '/admin')) {
  require_once __DIR__ . '/admin.php';
  return;
}

// Remove /api prefix if present
$path = preg_replace('#^/api#', '', $path);

// Remove leading/trailing slashes
$path = trim($path, '/');

// Split path into segments
$segments = $path ? explode('/', $path) : [];

// Router function
function route(string $method, array $segments): void
{
  try {
    // Handle empty path (API info)
    if (empty($segments) || $segments[0] === '') {
      handleApiInfo();
      return;
    }

    $resource = $segments[0];
    $action = $segments[1] ?? null;
    $id = $segments[2] ?? null;

    switch ($resource) {
      case 'events':
        handleEventsRoutes($method, $action, $id);
        break;

      case 'forms':
        handleFormRoutes($method, $action);
        break;

      case 'contact':
        handleContactRoutes($method, $action);
        break;

      case 'calendar':
        handleCalendarRoutes($method, $action, $id);
        break;

      case 'stammtisch-locations':
        handleStammtischLocationRoutes($method, $action, $id);
        break;

      default:
        Response::error('Endpoint not found', 404);
    }
  } catch (Exception $e) {
    error_log("API error: " . $e->getMessage());

    if (Config::get('app.debug')) {
      Response::error('Internal error: ' . $e->getMessage(), 500);
    } else {
      Response::error('Internal server error', 500);
    }
  }
}

// Handle API info
function handleApiInfo(): void
{
  Response::json([
    'success' => true,
    'data' => [
      'name' => 'Hypnose Stammtisch API',
      'version' => '1.0.0',
      'description' => 'Backend API for the Hypnose Stammtisch community calendar',
      'endpoints' => [
        'GET /events' => 'List all events with optional filters',
        'GET /events/upcoming' => 'Get upcoming events',
        'GET /events/featured' => 'Get featured events',
        'GET /events/meta' => 'Get event metadata (categories, etc.)',
        'GET /events/{id}' => 'Get single event by ID or slug',
        'POST /contact' => 'Submit contact form',
        'GET /calendar/feed' => 'Get ICS calendar feed',
        'GET /calendar/feed/{token}' => 'Get private ICS calendar feed',
        'GET /calendar/meta' => 'Get calendar metadata',
        'GET /calendar/event/{id}/ics' => 'Get ICS for single event',
        'GET /stammtisch-locations' => 'Get all published stammtisch locations',
        'GET /stammtisch-locations/meta' => 'Get stammtisch location metadata',
        'GET /stammtisch-locations/{id}' => 'Get single stammtisch location by ID'
      ],
      'documentation' => Config::get('app.url') . '/docs',
      'support' => 'support@hypnose-stammtisch.de'
    ]
  ]);
}

// Handle events routes
function handleEventsRoutes(string $method, ?string $action, ?string $id): void
{
  $controller = new EventsController();

  if ($method === 'GET') {
    if (!$action) {
      // GET /events
      $controller->index();
    } elseif ($action === 'upcoming') {
      // GET /events/upcoming
      $controller->upcoming();
    } elseif ($action === 'featured') {
      // GET /events/featured
      $controller->featured();
    } elseif ($action === 'meta') {
      // GET /events/meta
      $controller->meta();
    } elseif (is_numeric($action) && $id === 'ics') {
      // GET /events/{id}/ics
      $controller->downloadICS($action);
    } elseif (is_numeric($action) || !is_numeric($action)) {
      // GET /events/{id} or GET /events/{slug}
      $controller->show($action);
    } else {
      Response::json(['success' => false, 'error' => 'Invalid events endpoint'], 404);
    }
  } elseif ($method === 'POST') {
    if (!$action) {
      // POST /events (future admin feature)
      $controller->store();
    } elseif ($action === 'validate-rrule') {
      // POST /events/validate-rrule
      $controller->validateRRule();
    } elseif ($action === 'preview-recurring') {
      // POST /events/preview-recurring
      $controller->previewRecurring();
    } else {
      Response::json(['success' => false, 'error' => 'Invalid events endpoint'], 404);
    }
  } elseif ($method === 'PUT') {
    if (is_numeric($action)) {
      // PUT /events/{id} (future admin feature)
      $controller->update($action);
    } else {
      Response::error('Invalid events endpoint', 404);
    }
  } elseif ($method === 'DELETE') {
    if (is_numeric($action)) {
      // DELETE /events/{id} (future admin feature)
      $controller->destroy($action);
    } else {
      Response::error('Invalid events endpoint', 404);
    }
  } else {
    Response::error('Method not allowed', 405);
  }
}

// Handle contact routes
function handleContactRoutes(string $method, ?string $action): void
{
  $controller = new ContactController();

  if ($method === 'POST' && (!$action || $action === 'submit')) {
    // POST /contact
    $controller->submit();
  } else {
    Response::error('Invalid contact endpoint', 404);
  }
}

// Handle form routes
function handleFormRoutes(string $method, ?string $action): void
{
  $controller = new FormController();

  if ($method === 'POST') {
    switch ($action) {
      case 'submit-event':
        // POST /forms/submit-event
        $controller->submitEvent();
        break;

      case 'contact':
        // POST /forms/contact
        $controller->submitContact();
        break;

      default:
        Response::json(['success' => false, 'error' => 'Invalid form endpoint'], 404);
    }
  } else {
    Response::json(['success' => false, 'error' => 'Method not allowed'], 405);
  }
}

// Handle calendar routes
function handleCalendarRoutes(string $method, ?string $action, ?string $id): void
{
  $controller = new CalendarController();

  if ($method === 'GET') {
    if ($action === 'feed') {
      // GET /calendar/feed or GET /calendar/feed/{token}
      $controller->feed($id);
    } elseif ($action === 'meta') {
      // GET /calendar/meta
      $controller->meta();
    } elseif ($action === 'event' && $id && isset($_GET['format']) && $_GET['format'] === 'ics') {
      // GET /calendar/event/{id}?format=ics
      $controller->eventIcs($id);
    } elseif ($action === 'event' && $id) {
      // Alternative: GET /calendar/event/{id}/ics
      $segments = explode('/', $_SERVER['REQUEST_URI']);
      if (end($segments) === 'ics') {
        $controller->eventIcs($id);
      } else {
        Response::error('Invalid calendar endpoint', 404);
      }
    } else {
      Response::error('Invalid calendar endpoint', 404);
    }
  } else {
    Response::error('Method not allowed', 405);
  }
}

// Handle stammtisch location routes
function handleStammtischLocationRoutes(string $method, ?string $action, ?string $id): void
{
  $controller = new StammtischLocationController();

  if ($method === 'GET') {
    if (!$action) {
      // GET /stammtisch-locations
      $controller->getPublished();
    } elseif ($action === 'meta') {
      // GET /stammtisch-locations/meta
      $controller->getMeta();
    } elseif ($action) {
      // GET /stammtisch-locations/{id}
      $controller->getById($action);
    } else {
      Response::error('Invalid stammtisch-locations endpoint', 404);
    }
  } else {
    Response::error('Method not allowed', 405);
  }
}

// Execute router
route($method, $segments);
