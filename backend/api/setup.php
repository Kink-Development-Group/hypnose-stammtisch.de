<?php
// Web Wrapper für Setup: Weiterleitung auf ../setup.php
// Fallback: wenn im Deployment setup.php in übergeordnetem Verzeichnis fehlt, Fehlermeldung ausgeben
$target = __DIR__ . '/../setup.php';
if (!file_exists($target)) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'error' => 'setup.php not found (expecting ../setup.php)']);
  return;
}
require_once $target;
