<?php

declare(strict_types=1);

/**
 * Setup Command
 * Handles complete application setup and configuration
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\SqlStatementParser;

Config::load(__DIR__ . '/../..');

class SetupCommand
{
    private string $action = '';
    private array $options = [];

    public function __construct(array $args)
    {
        $this->parseArguments($args);
    }

    private function parseArguments(array $args): void
    {
        // Remove the script name from args if it's there
        if (!empty($args) && str_contains($args[0], 'setup.php')) {
            array_shift($args);
        }

        if (empty($args)) {
            $this->action = 'full';
        } elseif (in_array($args[0], ['--help', '-h'])) {
            $this->showHelp();
            exit(0);
        } else {
            $this->action = array_shift($args);
        }

        // Parse options
        for ($i = 0; $i < count($args); $i++) {
            $arg = $args[$i];

            if (str_starts_with($arg, '--')) {
                $key = substr($arg, 2);
                $value = isset($args[$i + 1]) && !str_starts_with($args[$i + 1], '--')
                    ? $args[++$i]
                    : true;
                $this->options[$key] = $value;
            }
        }
    }
    private function showHelp(): void
    {
        echo "Setup Command\n";
        echo "=============\n\n";
        echo "Usage: php cli.php setup [action] [options]\n\n";
        echo "Actions:\n";
        echo "  full         Complete application setup (default)\n";
        echo "  database     Setup database only\n";
        echo "  config       Setup configuration files\n";
        echo "  admin        Setup initial admin user\n";
        echo "  emails       Setup email addresses from .env configuration\n";
        echo "  validate     Validate setup requirements\n";
        echo "  reset        Reset application to fresh state\n\n";
        echo "Options:\n";
        echo "  --force      Force setup even if already configured\n";
        echo "  --skip-db    Skip database setup\n";
        echo "  --skip-admin Skip admin user creation\n";
        echo "  --admin-email EMAIL  Admin email address\n";
        echo "  --admin-pass PASS    Admin password\n";
        echo "  --verbose    Show detailed output\n\n";
        echo "Examples:\n";
        echo "  php cli.php setup\n";
        echo "  php cli.php setup database --force\n";
        echo "  php cli.php setup admin --admin-email admin@example.com\n";
        echo "  php cli.php setup emails --force\n\n";
    }

    public function run(): void
    {
        try {
            switch ($this->action) {
                case 'full':
                    $this->fullSetup();
                    break;
                case 'database':
                    $this->setupDatabase();
                    break;
                case 'config':
                    $this->setupConfig();
                    break;
                case 'admin':
                    $this->setupAdmin();
                    break;
                case 'emails':
                    $this->setupEmailAddresses();
                    break;
                case 'validate':
                    $this->validateSetup();
                    break;
                case 'reset':
                    $this->resetApplication();
                    break;
                default:
                    $this->output("Unknown action: {$this->action}", 'error');
                    $this->showHelp();
                    exit(1);
            }
        } catch (Exception $e) {
            $this->output("Setup failed: " . $e->getMessage(), 'error');
            exit(1);
        }
    }

    private function fullSetup(): void
    {
        $this->output("Starting complete application setup...", 'info');
        $this->output(str_repeat('=', 50), 'info');

        // Step 1: Validate requirements
        $this->output("\n1. Validating system requirements...", 'info');
        $this->validateRequirements();

        // Step 2: Setup configuration
        if (!($this->options['skip-config'] ?? false)) {
            $this->output("\n2. Setting up configuration...", 'info');
            $this->setupConfig();
        }

        // Step 3: Setup database
        if (!($this->options['skip-db'] ?? false)) {
            $this->output("\n3. Setting up database...", 'info');
            $this->setupDatabase();
        }    // Step 4: Run migrations
        $this->output("\n4. Running database migrations...", 'info');
        $this->runMigrations();

        // Step 5: Setup admin user
        if (!($this->options['skip-admin'] ?? false)) {
            $this->output("\n5. Setting up admin user...", 'info');
            $this->setupAdmin();
        }

        // Step 6: Setup email addresses
        $this->output("\n6. Setting up email addresses...", 'info');
        $this->setupEmailAddresses();

        // Step 7: Create directories
        $this->output("\n7. Creating required directories...", 'info');
        $this->createDirectories();

        // Step 8: Set permissions
        $this->output("\n8. Setting up permissions...", 'info');
        $this->setupPermissions();

        // Step 9: Final validation
        $this->output("\n9. Final validation...", 'info');
        $this->validateSetup();

        $this->output("\n" . str_repeat('=', 50), 'success');
        $this->output("Setup completed successfully!", 'success');
        $this->output("You can now access the application.", 'info');
    }

    private function validateRequirements(): void
    {
        $requirements = [
            'PHP Version >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
            'PDO Extension' => extension_loaded('pdo'),
            'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
            'cURL Extension' => extension_loaded('curl'),
            'JSON Extension' => extension_loaded('json'),
            'OpenSSL Extension' => extension_loaded('openssl'),
            'Composer Autoloader' => file_exists(__DIR__ . '/../../vendor/autoload.php'),
        ];

        $allPassed = true;

        foreach ($requirements as $requirement => $passed) {
            $status = $passed ? 'OK' : 'FAIL';
            $color = $passed ? 'success' : 'error';
            $this->output("  {$requirement}: {$status}", $color);

            if (!$passed) {
                $allPassed = false;
            }
        }

        if (!$allPassed) {
            throw new RuntimeException("System requirements not met. Please fix the failed requirements.");
        }

        $this->output("All requirements met!", 'success');
    }

    private function setupConfig(): void
    {
        $configDir = __DIR__ . '/../../config';
        $envFile = __DIR__ . '/../../.env';

        // Create config directory if it doesn't exist
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
            $this->output("Created config directory", 'info');
        }

        // Create .env file if it doesn't exist
        if (!file_exists($envFile) || ($this->options['force'] ?? false)) {
            $envTemplate = $this->getEnvTemplate();
            file_put_contents($envFile, $envTemplate);
            $this->output("Created .env configuration file", 'success');
            $this->output("IMPORTANT: Please edit .env file with your actual configuration!", 'warning');
        } else {
            $this->output("Configuration file already exists", 'info');
        }

        // Create app config file
        $appConfigFile = $configDir . '/app.php';
        if (!file_exists($appConfigFile) || ($this->options['force'] ?? false)) {
            $appConfig = $this->getAppConfigTemplate();
            file_put_contents($appConfigFile, $appConfig);
            $this->output("Created app configuration file", 'success');
        }

        // Create database config file
        $dbConfigFile = $configDir . '/database.php';
        if (!file_exists($dbConfigFile) || ($this->options['force'] ?? false)) {
            $dbConfig = $this->getDatabaseConfigTemplate();
            file_put_contents($dbConfigFile, $dbConfig);
            $this->output("Created database configuration file", 'success');
        }
    }

    private function setupDatabase(): void
    {
        try {
            // Test database connection
            Database::getConnection();
            $this->output("Database connection successful", 'success');

            // Check if database is already set up
            $tables = Database::fetchAll("SHOW TABLES");

            if (!empty($tables) && !(($this->options['force'] ?? false))) {
                $this->output("Database already contains tables", 'info');
                return;
            }

            $this->output("Database ready for setup", 'success');
        } catch (Exception $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    private function runMigrations(): void
    {
        $migrationFiles = glob(__DIR__ . '/../../migrations/*.sql');

        if (empty($migrationFiles)) {
            $this->output("No migration files found", 'warning');
            return;
        }

        sort($migrationFiles);

        // Create migrations table
        Database::getConnection()->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT PRIMARY KEY AUTO_INCREMENT,
                version VARCHAR(50) NOT NULL UNIQUE,
                description TEXT,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Get executed migrations
        $executed = Database::fetchAll("SELECT version FROM migrations ORDER BY version");
        $executedVersions = array_column($executed, 'version');

        $migrationCount = 0;

        foreach ($migrationFiles as $file) {
            $filename = basename($file);
            $version = substr($filename, 0, 3);

            if (in_array($version, $executedVersions)) {
                $this->output("Migration {$version} already executed", 'info');
                continue;
            }

            $this->output("Executing migration {$version}...", 'info');

            $sql = file_get_contents($file);
            $this->executeMigrationSQL($sql, $version, $filename);

            $migrationCount++;
        }

        if ($migrationCount > 0) {
            $this->output("Executed {$migrationCount} migration(s)", 'success');
        } else {
            $this->output("No new migrations to execute", 'info');
        }
    }

    private function executeMigrationSQL(string $sql, string $version, string $filename): void
    {
        // Split SQL into statements
        $statements = SqlStatementParser::parse($sql);

        $transactionStarted = false;
        if (!empty($statements) && !Database::inTransaction()) {
            try {
                $transactionStarted = Database::beginTransaction();
            } catch (Throwable $e) {
                $transactionStarted = false;
            }
        }

        try {
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    Database::getConnection()->exec($statement);
                }
            }

            // Record migration (only if not already tracked)
            $alreadyRecorded = Database::fetchOne(
                "SELECT version FROM migrations WHERE version = ?",
                [$version]
            );

            if (!$alreadyRecorded) {
                Database::execute(
                    "INSERT INTO migrations (version, description) VALUES (?, ?)",
                    [$version, "Migration from file: {$filename}"]
                );
            }

            if ($transactionStarted && Database::inTransaction()) {
                Database::commit();
            }
        } catch (Exception $e) {
            if ($transactionStarted && Database::inTransaction()) {
                Database::rollback();
            }
            throw new RuntimeException("Migration {$version} failed: " . $e->getMessage());
        }
    }

    private function setupAdmin(): void
    {
        // Check if head admin already exists
        $existing = Database::fetchOne("SELECT id FROM users WHERE role = 'head' LIMIT 1");

        if ($existing && !(($this->options['force'] ?? false))) {
            $this->output("Head admin already exists", 'info');
            return;
        }

        $email = $this->options['admin-email'] ?? $this->prompt('Admin email address');
        $password = $this->options['admin-pass'] ?? $this->promptPassword('Admin password');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format");
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException("Password must be at least 8 characters long");
        }

        // Check if user with this email exists
        $existingUser = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existingUser) {
            // Update existing user to head admin
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            Database::execute(
                "UPDATE users SET password_hash = ?, role = 'head', is_active = 1 WHERE email = ?",
                [$hashedPassword, $email]
            );
            $this->output("Updated existing user to head admin", 'success');
        } else {
            // Create new head admin
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            Database::execute(
                "INSERT INTO users (username, email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, 'head', 1, NOW())",
                [explode('@', $email)[0], $email, $hashedPassword]
            );
            $this->output("Created head admin user", 'success');
        }

        $this->output("Admin credentials:", 'info');
        $this->output("Email: {$email}", 'info');
        $this->output("Password: {$password}", 'warning');
        $this->output("IMPORTANT: Change this password after first login!", 'error');
    }

    /**
     * Setup email addresses from environment configuration
     */
    private function setupEmailAddresses(): void
    {
        // Check if email addresses already exist
        $existing = Database::fetchOne("SELECT COUNT(*) as count FROM admin_email_addresses");

        if (($existing['count'] ?? 0) > 0 && !(($this->options['force'] ?? false))) {
            $this->output("Email addresses already configured ({$existing['count']} found)", 'info');
            return;
        }

        // Define email addresses from environment configuration
        // Using Config::get() with dotted keys for structured config access
        $emailConfigs = [
            [
                'config_key' => 'contact.general',
                'display_name' => 'Allgemein',
                'department' => 'allgemein',
                'is_default' => true,
            ],
            [
                'config_key' => 'contact.events',
                'display_name' => 'Veranstaltungen',
                'department' => 'events',
                'is_default' => false,
            ],
            [
                'config_key' => 'contact.conduct',
                'display_name' => 'Verhaltenskodex',
                'department' => 'conduct',
                'is_default' => false,
            ],
            [
                'config_key' => 'contact.support',
                'display_name' => 'Support',
                'department' => 'support',
                'is_default' => false,
            ],
            [
                'config_key' => 'mail.from_email',
                'display_name' => Config::get('mail.from_name', 'Hypnose Stammtisch'),
                'department' => 'noreply',
                'is_default' => false,
            ],
        ];

        $addedCount = 0;

        foreach ($emailConfigs as $config) {
            $email = Config::get($config['config_key']);

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            // Check if this email already exists
            $existingEmail = Database::fetchOne(
                "SELECT id FROM admin_email_addresses WHERE email = ?",
                [$email]
            );

            if ($existingEmail) {
                $this->output("Email '{$email}' already exists, skipping", 'info');
                continue;
            }

            try {
                Database::execute(
                    "INSERT INTO admin_email_addresses (email, display_name, department, is_default, is_active)
           VALUES (?, ?, ?, ?, 1)",
                    [
                        $email,
                        $config['display_name'],
                        $config['department'],
                        $config['is_default'] ? 1 : 0,
                    ]
                );
                $addedCount++;
                $this->output("Added email: {$config['display_name']} <{$email}>", 'success');
            } catch (Exception $e) {
                $this->output("Failed to add email '{$email}': " . $e->getMessage(), 'warning');
            }
        }

        if ($addedCount === 0) {
            $this->output("No email addresses configured in .env file", 'warning');
            $this->output("Please configure CONTACT_GENERAL, CONTACT_EVENTS, etc. in your .env file", 'info');
        } else {
            $this->output("Added {$addedCount} email address(es)", 'success');
        }
    }

    private function createDirectories(): void
    {
        $directories = [
            __DIR__ . '/../../logs' => 'Logs directory',
            __DIR__ . '/../../cache' => 'Cache directory',
            __DIR__ . '/../../temp' => 'Temporary files directory',
            __DIR__ . '/../../uploads' => 'Uploads directory',
        ];

        foreach ($directories as $dir => $description) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->output("Created {$description}", 'info');

                // Create .gitkeep file
                file_put_contents($dir . '/.gitkeep', '');
            } else {
                $this->output("{$description} already exists", 'info');
            }
        }
    }

    private function setupPermissions(): void
    {
        $directories = [
            __DIR__ . '/../../logs',
            __DIR__ . '/../../cache',
            __DIR__ . '/../../temp',
            __DIR__ . '/../../uploads',
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                chmod($dir, 0755);
                $this->output("Set permissions for " . basename($dir), 'info');
            }
        }
    }

    private function validateSetup(): void
    {
        $checks = [
            'Database connection' => function () {
                Database::getConnection();
                return true;
            },
            'Required tables exist' => function () {
                $tables = ['users', 'events', 'contact_submissions', 'migrations'];
                foreach ($tables as $table) {
                    $exists = Database::fetchOne("SHOW TABLES LIKE '{$table}'");
                    if (!$exists) {
                        return ['status' => 'fail', 'message' => "Missing table: {$table}"];
                    }
                }
                return true;
            },
            'Head admin exists' => function () {
                $admin = Database::fetchOne("SELECT id FROM users WHERE role = 'head' LIMIT 1");
                return !empty($admin);
            },
            'Configuration loaded' => function () {
                $hasEnvHost = !empty($_ENV['DB_HOST']);
                $hasEnvName = !empty($_ENV['DB_DATABASE'] ?? $_ENV['DB_NAME'] ?? null);

                if ($hasEnvHost && $hasEnvName) {
                    return true;
                }

                $configHost = Config::get('db.host');
                $configName = Config::get('db.name');

                if (!empty($configHost) && !empty($configName)) {
                    return [
                        'status' => 'warning',
                        'message' => 'Environment variables missing; using default configuration values. Update your .env file.',
                    ];
                }

                return ['status' => 'fail', 'message' => 'Database configuration missing. Check your .env file.'];
            },
            'Required directories exist' => function () {
                $dirs = ['logs', 'cache', 'temp', 'uploads'];
                foreach ($dirs as $dir) {
                    if (!is_dir(__DIR__ . '/../../' . $dir)) {
                        return ['status' => 'fail', 'message' => "Missing directory: {$dir}"];
                    }
                }
                return true;
            },
        ];

        $allPassed = true;

        foreach ($checks as $check => $test) {
            try {
                $result = call_user_func($test);

                $status = 'OK';
                $color = 'success';
                $message = null;
                $didFail = false;

                if (is_array($result)) {
                    $state = strtolower($result['status'] ?? 'ok');
                    $message = $result['message'] ?? null;

                    if ($state === 'warning') {
                        $status = 'WARN';
                        $color = 'warning';
                    } elseif (in_array($state, ['fail', 'error'], true)) {
                        $status = 'FAIL';
                        $color = 'error';
                        $didFail = true;
                    }
                } else {
                    $isSuccess = (bool) $result;
                    if (!$isSuccess) {
                        $status = 'FAIL';
                        $color = 'error';
                        $didFail = true;
                    }
                }

                $this->output("  {$check}: {$status}" . ($message ? " - {$message}" : ''), $color);

                if ($didFail) {
                    $allPassed = false;
                }
            } catch (Exception $e) {
                $this->output("  {$check}: ERROR - " . $e->getMessage(), 'error');
                $allPassed = false;
            }
        }

        if (!$allPassed) {
            throw new RuntimeException("Setup validation failed. Please check the errors above.");
        }

        $this->output("All setup checks passed!", 'success');
    }

    private function resetApplication(): void
    {
        if (!($this->options['force'] ?? false)) {
            $this->output("WARNING: This will reset the entire application!", 'error');
            echo "Type 'RESET APPLICATION' to confirm: ";
            $confirm = trim(fgets(STDIN));

            if ($confirm !== 'RESET APPLICATION') {
                $this->output("Operation cancelled.", 'info');
                return;
            }
        }

        $this->output("Resetting application...", 'warning');

        // Drop all database tables
        $connection = Database::getConnection();
        $connection->exec("SET FOREIGN_KEY_CHECKS = 0");

        $tables = Database::fetchAll("SHOW TABLES");
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            $connection->exec("DROP TABLE IF EXISTS `{$tableName}`");
        }

        $connection->exec("SET FOREIGN_KEY_CHECKS = 1");

        // Clear directories
        $directories = ['logs', 'cache', 'temp', 'uploads'];
        foreach ($directories as $dir) {
            $fullPath = __DIR__ . '/../../' . $dir;
            if (is_dir($fullPath)) {
                $files = glob($fullPath . '/*');
                foreach ($files as $file) {
                    if (is_file($file) && basename($file) !== '.gitkeep') {
                        unlink($file);
                    }
                }
            }
        }

        $this->output("Application reset completed", 'success');
        $this->output("Run 'php cli.php setup' to set up the application again", 'info');
    }

    private function getEnvTemplate(): string
    {
        return <<<ENV
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=hypnose_stammtisch
DB_USERNAME=root
DB_PASSWORD=

# Application Configuration
APP_ENV=development
APP_DEBUG=true
BASE_URL=http://localhost:5173

# Email Configuration
SMTP_HOST=localhost
SMTP_PORT=587
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_ENCRYPTION=tls
FROM_EMAIL=noreply@hypnose-stammtisch.de
FROM_NAME="Hypnose Stammtisch"

# Security
SESSION_LIFETIME=86400
CSRF_TOKEN_LIFETIME=3600

# Calendar
CALENDAR_CACHE_DURATION=3600
ENV;
    }

    private function getAppConfigTemplate(): string
    {
        return <<<PHP
<?php

return [
    'app' => [
        'name' => 'Hypnose Stammtisch',
        'version' => '1.0.0',
        'environment' => \$_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var(\$_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'base_url' => \$_ENV['BASE_URL'] ?? '',
    ],

    'session' => [
        'lifetime' => (int)(\$_ENV['SESSION_LIFETIME'] ?? 86400),
        'name' => 'hypnose_session',
    ],

    'security' => [
        'csrf_token_lifetime' => (int)(\$_ENV['CSRF_TOKEN_LIFETIME'] ?? 3600),
        'password_min_length' => 8,
    ],

    'calendar' => [
        'cache_duration' => (int)(\$_ENV['CALENDAR_CACHE_DURATION'] ?? 3600),
    ],
];
PHP;
    }

    private function getDatabaseConfigTemplate(): string
    {
        return <<<PHP
<?php

return [
    'default' => [
        'driver' => 'mysql',
        'host' => \$_ENV['DB_HOST'] ?? 'localhost',
        'port' => (int)(\$_ENV['DB_PORT'] ?? 3306),
        'database' => \$_ENV['DB_DATABASE'] ?? '',
        'username' => \$_ENV['DB_USERNAME'] ?? '',
        'password' => \$_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
];
PHP;
    }

    private function prompt(string $message): string
    {
        echo $message . ': ';
        return trim(fgets(STDIN));
    }

    private function promptPassword(string $message): string
    {
        echo $message . ': ';

        // Hide password input on Unix systems
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            system('stty -echo');
            $password = trim(fgets(STDIN));
            system('stty echo');
            echo "\n";
        } else {
            $password = trim(fgets(STDIN));
        }

        return $password;
    }

    private function output(string $message, string $type = 'info'): void
    {
        $colors = [
            'info' => "\033[36m",    // Cyan
            'success' => "\033[32m", // Green
            'warning' => "\033[33m", // Yellow
            'error' => "\033[31m",   // Red
            'debug' => "\033[37m",   // White
        ];

        $reset = "\033[0m";
        $color = $colors[$type] ?? $colors['info'];

        echo $color . $message . $reset . "\n";
    }
}

// Execute command
$setup = new SetupCommand($argv);
$setup->run();
