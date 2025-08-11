<?php
// DEPRECATED: setup-admin.php wurde entfernt. Bitte backend/setup.php oder CLI nutzen.
http_response_code(410);
header('Content-Type: application/json');
echo json_encode(['error' => 'setup-admin deprecated']);
