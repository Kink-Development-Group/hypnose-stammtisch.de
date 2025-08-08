<?php
// Simple test file for backend connection
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
  'php_version' => PHP_VERSION,
  'request_method' => $_SERVER['REQUEST_METHOD'],
  'request_uri' => $_SERVER['REQUEST_URI']
]);
