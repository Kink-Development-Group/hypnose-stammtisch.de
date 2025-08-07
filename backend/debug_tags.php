<?php
require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

// Load configuration
Config::load(__DIR__);

try {
  $events = Database::fetchAll("SELECT id, title, tags FROM events LIMIT 5");

  echo "Events in database:\n";
  foreach ($events as $event) {
    echo "ID: {$event['id']}, Title: {$event['title']}, Tags: ";
    var_dump($event['tags']);
    echo "Type: " . gettype($event['tags']) . "\n";
    echo "---\n";
  }
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
