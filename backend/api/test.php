<?php
// Simple connectivity test for local development only.

// Disabled in production: this endpoint exists purely to verify the frontend
// can reach the backend during local development. Serving it on a live host
// would leak environment details (PHP version, request internals) to anyone.
// Guard must run before CORS headers and the OPTIONS early-return so that
// production never answers preflights with localhost CORS headers.
if ((getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production')) === 'production') {
  http_response_code(404);
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'error' => 'Not found']);
  exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

echo json_encode([
  'success' => true,
  'message' => 'Backend is working',
  'server_time' => date('Y-m-d H:i:s'),
]);
