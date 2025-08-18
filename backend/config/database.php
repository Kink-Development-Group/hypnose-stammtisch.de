<?php

return [
    'default' => [
        'driver' => 'mysql',
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => (int)($_ENV['DB_PORT'] ?? 3306),
        // Support both DB_DATABASE and DB_NAME (README variants)
        'database' => $_ENV['DB_DATABASE'] ?? $_ENV['DB_NAME'] ?? '',
        // Support DB_USERNAME and DB_USER
        'username' => $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? '',
        // Support DB_PASSWORD and DB_PASS
        'password' => $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
];
