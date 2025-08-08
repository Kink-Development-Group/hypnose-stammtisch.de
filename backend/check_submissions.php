<?php
require_once 'vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load('.');
try {
  $result = Database::fetchAll('SHOW TABLES LIKE "submissions"');
  if (empty($result)) {
    echo 'submissions table does not exist';
  } else {
    echo 'submissions table exists';
  }
} catch (Exception $e) {
  echo 'Error: ' . $e->getMessage();
}
