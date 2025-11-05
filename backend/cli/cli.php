<?php

declare(strict_types=1);

/**
 * Hypnose Stammtisch CLI Tool
 * Unified command-line interface for all backend operations
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;

// Load configuration
Config::load(__DIR__ . '/..');
$appName = Config::get('app.name', 'Hypnose Stammtisch');

// Available commands
$commands = [
    'setup' => [
        'description' => 'Complete application setup and configuration',
        'file' => 'setup.php'
    ],
    'migrate' => [
        'description' => 'Database migration operations',
        'file' => 'migrate.php'
    ],
    'admin' => [
        'description' => 'Admin user management',
        'file' => 'admin.php'
    ],
    'database' => [
        'description' => 'Database utilities and maintenance',
        'file' => 'database.php'
    ],
    'db' => [
        'description' => 'Database utilities (alias for database)',
        'file' => 'database.php'
    ],
    'dev' => [
        'description' => 'Development utilities and tools',
        'file' => 'dev.php'
    ],
    'test' => [
        'description' => 'Run tests and validations',
        'file' => 'test.php'
    ]
];

function showHelp(array $commands, string $appName): void
{
    echo $appName . " CLI Tool\n";
    echo str_repeat('=', strlen($appName . ' CLI Tool')) . "\n\n";
    echo "A unified command-line interface for managing the {$appName} application.\n";
    echo "This tool replaces all scattered scripts with organized, professional commands.\n\n";
    echo "Usage: php cli.php <command> [options]\n\n";
    echo "Available Commands:\n";

    foreach ($commands as $command => $info) {
        echo sprintf("  %-12s %s\n", $command, $info['description']);
    }

    echo "\nQuick Start:\n";
    echo "  php cli.php setup              # Complete application setup\n";
    echo "  php cli.php migrate             # Run database migrations\n";
    echo "  php cli.php admin create        # Create new admin user\n";
    echo "  php cli.php dev serve           # Start development server\n";
    echo "  php cli.php test all            # Run all tests\n\n";
    echo "Use 'php cli.php <command> --help' for detailed command-specific help.\n\n";
    echo "Detailed Examples:\n";
    echo "  php cli.php setup --admin-email admin@example.com\n";
    echo "  php cli.php migrate --fresh --seed\n";
    echo "  php cli.php admin list\n";
    echo "  php cli.php db backup --file backup.sql\n";
    echo "  php cli.php dev serve --port 8080\n";
    echo "  php cli.php test api --verbose\n\n";
}

function executeCommand(string $command, array $args, array $commands): void
{
    global $appName;

    if (!isset($commands[$command])) {
        echo "Error: Unknown command '$command'\n\n";
        showHelp($commands, $appName);
        exit(1);
    }

    $commandFile = __DIR__ . '/commands/' . $commands[$command]['file'];

    if (!file_exists($commandFile)) {
        echo "Error: Command file not found: {$commandFile}\n";
        exit(1);
    }

    // Set command line arguments for the command script
    global $argv;
    $originalArgv = $argv;

    // Set argv to include the command file as first argument and pass remaining args
    $argv = array_merge([$commandFile], $args);

    try {
        require $commandFile;
    } catch (Exception $e) {
        echo "Error executing command: " . $e->getMessage() . "\n";
        exit(1);
    } finally {
        // Restore original argv
        $argv = $originalArgv;
    }
}

// Main execution
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line.');
}

$args = array_slice($argv, 1);

if (empty($args) || in_array($args[0], ['-h', '--help', 'help'])) {
    showHelp($commands, $appName);
    exit(0);
}

$command = $args[0];
$commandArgs = array_slice($args, 1);

executeCommand($command, $commandArgs, $commands);
