<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$documentRoot = __DIR__;

if ($uri !== '/' && $uri !== '' && str_contains($uri, '..')) {
  // Prevent directory traversal attempts
  http_response_code(400);
  exit;
}

$requestedPath = $documentRoot . $uri;
$realPath = realpath($requestedPath);

if ($uri !== '/' && $realPath !== false && str_starts_with($realPath, $documentRoot) && is_file($realPath)) {
  // Let the built-in server handle existing static files
  return false;
}

require $documentRoot . '/index.php';
