<?php
// Drops and recreates the database, then runs baseline migration.
// WARNING: Destructive. Use only in dev.
require __DIR__ . '/../vendor/autoload.php';
\HypnoseStammtisch\Config\Config::load(__DIR__ . '/..');

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Config\Config;

$config = Config::get('db');
$dbName = $config['name'];
$pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $config['host'], $config['port']), $config['user'], $config['pass'], [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
$pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
$pdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
Database::close();
Database::getConnection();
require __DIR__ . '/../migrations/migrate.php';
runMigrations();
echo "Fresh rebuild done.\n";
