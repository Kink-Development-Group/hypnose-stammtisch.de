<?php
require_once 'vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load('.');

echo "=== Users table structure ===" . PHP_EOL;
$users = Database::fetchAll('DESCRIBE users');
foreach ($users as $col) {
  echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . $col['Key'] . PHP_EOL;
}

echo PHP_EOL . "=== Event series structure ===" . PHP_EOL;
$series = Database::fetchAll('DESCRIBE event_series');
foreach ($series as $col) {
  echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . $col['Key'] . PHP_EOL;
}

echo PHP_EOL . "=== Message notes structure ===" . PHP_EOL;
$notes = Database::fetchAll('DESCRIBE message_notes');
foreach ($notes as $col) {
  echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . $col['Key'] . PHP_EOL;
}
