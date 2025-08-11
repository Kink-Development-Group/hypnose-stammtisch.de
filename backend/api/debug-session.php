<?php
// DEPRECATED: debug-session.php wurde entfernt. Nicht in Produktion verwenden.
http_response_code(410);
header('Content-Type: application/json');
echo json_encode(['error' => 'debug-session deprecated']);
