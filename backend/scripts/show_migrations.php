<?php
require __DIR__ . '/../vendor/autoload.php';
\HypnoseStammtisch\Config\Config::load(__DIR__ . '/..');
$pdo = \HypnoseStammtisch\Database\Database::getConnection();
$res = $pdo->query('SELECT version, description, executed_at FROM migrations ORDER BY version');
foreach ($res as $row) {
  echo $row['version'] . ' | ' . $row['executed_at'] . ' | ' . $row['description'] . PHP_EOL;
}
