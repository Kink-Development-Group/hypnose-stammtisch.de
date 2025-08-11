<?php
require __DIR__ . '/../vendor/autoload.php';
\HypnoseStammtisch\Config\Config::load(__DIR__ . '/..');
$pdo = \HypnoseStammtisch\Database\Database::getConnection();
$dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
echo "Current database: {$dbName}\n";
echo "All tables:\n";
$all = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
foreach ($all as $row) {
  echo '  - ' . $row[0] . "\n";
}
echo "\nSelected presence check:\n";
$tables = ['migrations', 'event_series', 'events', 'contact_submissions', 'event_registrations', 'users', 'user_twofa_backup_codes', 'message_notes', 'message_responses', 'admin_email_addresses'];
foreach ($tables as $t) {
  $stmt = $pdo->query("SHOW TABLES LIKE '" . $t . "'");
  echo str_pad($t, 28) . ': ' . ($stmt->fetchColumn() ? 'present' : 'MISSING') . PHP_EOL;
}
