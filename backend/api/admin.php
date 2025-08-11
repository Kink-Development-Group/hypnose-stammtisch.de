<?php

declare(strict_types=1);

/**
 * Admin API Router
 * Handles all admin panel API requests
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Controllers\AdminAuthController;
use HypnoseStammtisch\Controllers\AdminEventsController;
use HypnoseStammtisch\Controllers\AdminMessagesController;
use HypnoseStammtisch\Controllers\AdminUsersController;
use HypnoseStammtisch\Utils\Response;

// Load configuration
Config::load(__DIR__ . '/..');

// Handle CORS preflight
Response::handlePreflight();

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove /api/admin prefix
$path = preg_replace('#^/api/admin#', '', $path);
$path = rtrim($path, '/') ?: '/';

// Split path into segments
$segments = array_filter(explode('/', $path));

try {
  // Route authentication endpoints
  if ($path === '/auth/login' && $method === 'POST') {
    AdminAuthController::login();
    return;
  }

  if ($path === '/auth/logout' && $method === 'POST') {
    AdminAuthController::logout();
    return;
  }

  if ($path === '/auth/status' && $method === 'GET') {
    AdminAuthController::status();
    return;
  }

  if ($path === '/auth/csrf' && $method === 'GET') {
    AdminAuthController::csrf();
    return;
  }

  if ($path === '/auth/2fa/setup' && $method === 'POST') {
    \HypnoseStammtisch\Controllers\AdminAuthController::twofaSetup();
    return;
  }
  if ($path === '/auth/2fa/verify' && $method === 'POST') {
    \HypnoseStammtisch\Controllers\AdminAuthController::twofaVerify();
    return;
  }
  if ($path === '/auth/2fa/backup-codes/generate' && $method === 'POST') {
    AdminAuthController::twofaBackupGenerate();
    return;
  }
  if ($path === '/auth/2fa/backup-codes/status' && $method === 'GET') {
    AdminAuthController::twofaBackupStatus();
    return;
  }

  // Route users endpoints (only for head admins)
  if (str_starts_with($path, '/users')) {
    if ($path === '/users') {
      if ($method === 'GET') {
        AdminUsersController::index();
        return;
      } elseif ($method === 'POST') {
        AdminUsersController::create();
        return;
      }
    } elseif ($path === '/users/permissions') {
      if ($method === 'GET') {
        AdminUsersController::permissions();
        return;
      }
    } elseif (preg_match('#^/users/([a-zA-Z0-9\-]+)$#', $path, $matches)) {
      $id = (string)$matches[1];
      if ($method === 'GET') {
        AdminUsersController::show($id);
        return;
      } elseif ($method === 'PUT') {
        AdminUsersController::update($id);
        return;
      } elseif ($method === 'DELETE') {
        AdminUsersController::delete($id);
        return;
      }
    }
  }

  // Route events endpoints
  if (str_starts_with($path, '/events')) {
    if ($path === '/events') {
      if ($method === 'GET') {
        AdminEventsController::index();
        return;
      } elseif ($method === 'POST') {
        AdminEventsController::create();
        return;
      }
    } elseif (preg_match('#^/events/([a-zA-Z0-9\-]+)$#', $path, $matches)) {
      $id = (string)$matches[1];
      if ($method === 'PUT') {
        AdminEventsController::update($id);
        return;
      } elseif ($method === 'DELETE') {
        AdminEventsController::delete($id);
        return;
      }
    }
  }

  // Route messages endpoints
  if (str_starts_with($path, '/messages')) {
    if ($path === '/messages') {
      if ($method === 'GET') {
        AdminMessagesController::index();
        return;
      }
    } elseif ($path === '/messages/stats') {
      if ($method === 'GET') {
        AdminMessagesController::stats();
        return;
      }
    } elseif ($path === '/messages/email-addresses') {
      if ($method === 'GET') {
        AdminMessagesController::getEmailAddresses();
        return;
      }
    } elseif (preg_match('#^/messages/([a-zA-Z0-9\-]+)$#', $path, $matches)) {
      $id = $matches[1]; // Pass as string to match controller expectation
      if ($method === 'GET') {
        AdminMessagesController::show($id);
        return;
      } elseif ($method === 'DELETE') {
        AdminMessagesController::delete($id);
        return;
      }
    } elseif (preg_match('#^/messages/([a-zA-Z0-9\-]+)/status$#', $path, $matches)) {
      $id = $matches[1]; // Pass as string to match controller expectation
      if ($method === 'PATCH') {
        AdminMessagesController::updateStatus($id);
        return;
      }
    } elseif (preg_match('#^/messages/([a-zA-Z0-9\-]+)/responded$#', $path, $matches)) {
      $id = $matches[1]; // Pass as string to match controller expectation
      if ($method === 'PATCH') {
        AdminMessagesController::markResponded($id);
        return;
      }
    } elseif (preg_match('#^/messages/([a-zA-Z0-9\-]+)/notes$#', $path, $matches)) {
      $id = $matches[1];
      if ($method === 'GET') {
        AdminMessagesController::getNotes($id);
        return;
      } elseif ($method === 'POST') {
        AdminMessagesController::addNote($id);
        return;
      }
    } elseif (preg_match('#^/messages/([a-zA-Z0-9\-]+)/notes/([a-zA-Z0-9\-]+)$#', $path, $matches)) {
      $messageId = $matches[1];
      $noteId = $matches[2];
      if ($method === 'PUT') {
        AdminMessagesController::updateNote($messageId, $noteId);
        return;
      } elseif ($method === 'DELETE') {
        AdminMessagesController::deleteNote($messageId, $noteId);
        return;
      }
    } elseif (preg_match('#^/messages/([a-zA-Z0-9\-]+)/response$#', $path, $matches)) {
      $id = $matches[1];
      if ($method === 'POST') {
        AdminMessagesController::sendResponse($id);
        return;
      }
    } elseif (preg_match('#^/messages/([a-zA-Z0-9\-]+)/responses$#', $path, $matches)) {
      $id = $matches[1];
      if ($method === 'GET') {
        AdminMessagesController::getResponses($id);
        return;
      }
    }
  }

  // Route not found
  Response::notFound(['message' => 'API endpoint not found']);
} catch (Throwable $e) {
  error_log("Admin API Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

  if (Config::get('app.debug', false)) {
    Response::error('Internal server error: ' . $e->getMessage(), 500);
  } else {
    Response::error('Internal server error', 500);
  }
}
