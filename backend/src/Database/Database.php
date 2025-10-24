<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Database;

use HypnoseStammtisch\Config\Config;
use PDO;
use PDOException;

/**
 * Database connection manager
 */
class Database
{
    private static ?PDO $connection = null;

    /**
     * Get database connection
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::connect();
        }

        return self::$connection;
    }

    /**
     * Create database connection
     */
    private static function connect(): void
    {
        try {
            $config = Config::get('db');

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['name'],
                $config['charset']
            );

            self::$connection = new PDO(
                $dsn,
                $config['user'],
                $config['pass'],
                $config['options'] + [\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true]
            );

            // Set timezone
            $timezone = Config::get('app.timezone', 'Europe/Berlin');
            self::$connection->exec("SET time_zone = '{$timezone}'");
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());

            if (Config::get('app.debug')) {
                throw new \RuntimeException("Database connection failed: " . $e->getMessage());
            } else {
                throw new \RuntimeException("Database connection failed");
            }
        }
    }

    /**
     * Execute a prepared statement
     */
    public static function execute(string $sql, array $params = []): \PDOStatement
    {
        $connection = self::getConnection();
        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::execute($sql, $params)->fetchAll();
    }

    /**
     * Fetch single row
     */
    public static function fetchOne(string $sql, array $params = []): array|false
    {
        return self::execute($sql, $params)->fetch();
    }

    /**
     * Insert record and return last insert ID
     */
    public static function insert(string $sql, array $params = []): string|false
    {
        self::execute($sql, $params);
        return self::getConnection()->lastInsertId();
    }

    /**
     * Get last insert ID
     */
    public static function lastInsertId(): string|false
    {
        return self::getConnection()->lastInsertId();
    }

    /**
     * Execute an INSERT statement and return the last insert ID
     * This is safer than execute() + lastInsertId() as it's atomic
     */
    public static function insertAndGetId(string $sql, array $params = []): string|false
    {
        self::execute($sql, $params);
        return self::lastInsertId();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }

    /**
     * Check if we're in a transaction
     */
    public static function inTransaction(): bool
    {
        return self::getConnection()->inTransaction();
    }

    /**
     * Close database connection
     */
    public static function close(): void
    {
        self::$connection = null;
    }
}
