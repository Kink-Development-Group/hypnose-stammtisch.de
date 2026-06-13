<?php

declare(strict_types=1);

/**
 * CI smoke-test assertion for the database migration runner.
 *
 * Run AFTER `php migrations/migrate.php migrate` has executed (ideally twice,
 * to also cover idempotency). Verifies against the live database connection that:
 *
 *   1. every migration file NNN_*.sql is recorded in the `migrations` table,
 *   2. migration 012 in particular seeded exactly one row into
 *      `admin_email_addresses` (and re-running the runner did not add a second),
 *   3. that seeded row matches the expected default sender.
 *
 * Exits non-zero with a human-readable message on the first failed assertion so
 * the workflow step fails loudly instead of silently passing.
 */

require __DIR__ . '/../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__ . '/..');

$failures = [];

function check(array &$failures, string $label, bool $ok, string $detail = ''): void
{
    $status = $ok ? 'OK  ' : 'FAIL';
    echo "[{$status}] {$label}" . ($detail !== '' ? " — {$detail}" : '') . PHP_EOL;
    if (!$ok) {
        $failures[] = $label;
    }
}

$pdo = Database::getConnection();

// 1. Every migration file must be recorded in the migrations table.
$migrationDir = __DIR__ . '/../migrations';
$files = glob($migrationDir . '/[0-9][0-9][0-9]_*.sql') ?: [];
$expectedVersions = [];
foreach ($files as $file) {
    $expectedVersions[substr(basename($file), 0, 3)] = true;
}
$expectedVersions = array_keys($expectedVersions);
sort($expectedVersions, SORT_STRING);

$recorded = $pdo->query('SELECT version FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
$missing = array_diff($expectedVersions, $recorded);
check(
    $failures,
    'all migration files recorded in migrations table',
    $missing === [],
    $missing === []
        ? count($expectedVersions) . ' versions recorded'
        : 'missing: ' . implode(', ', $missing)
);

// 2. Migration 012 specifically must be recorded.
check(
    $failures,
    'migration 012 recorded',
    in_array('012', $recorded, true)
);

// 3. Migration 012 seeded exactly one admin email address (idempotency).
$count = (int) $pdo->query('SELECT COUNT(*) FROM admin_email_addresses')->fetchColumn();
check(
    $failures,
    'admin_email_addresses seeded exactly once (idempotent)',
    $count === 1,
    "row count = {$count}"
);

// 4. The seeded row matches the expected default sender.
$row = $pdo->query(
    'SELECT email, is_default, is_active FROM admin_email_addresses LIMIT 1'
)->fetch(PDO::FETCH_ASSOC);
$rowOk = $row !== false
    && $row['email'] === 'info@hypnose-stammtisch.de'
    && (int) $row['is_default'] === 1
    && (int) $row['is_active'] === 1;
check(
    $failures,
    'seeded default sender matches migration 012',
    $rowOk,
    $row === false ? 'no row found' : json_encode($row)
);

echo PHP_EOL;
if ($failures !== []) {
    echo 'Migration verification FAILED: ' . implode('; ', $failures) . PHP_EOL;
    exit(1);
}

echo 'Migration verification passed.' . PHP_EOL;
