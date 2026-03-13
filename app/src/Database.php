<?php
/**
 * LicenseRadar — PDO Database Singleton
 * Thread-safe, lazy-initialized database connection with prepared statement helpers.
 */

declare(strict_types=1);

namespace LicenseRadar;

use PDO;
use PDOStatement;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    /**
     * Get the PDO connection (lazy singleton).
     */
    public static function connect(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = Config::get('DB_HOST', 'localhost');
        $port = Config::get('DB_PORT', '3306');
        $name = Config::get('DB_NAME');
        $user = Config::get('DB_USER');
        $pass = Config::get('DB_PASS');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        } catch (PDOException $e) {
            if (Config::getBool('APP_DEBUG')) {
                throw $e;
            }
            http_response_code(500);
            exit('Database connection failed. Please check your .env configuration.');
        }

        return self::$pdo;
    }

    /**
     * Execute a prepared query and return the statement.
     *
     * @param array<int|string, mixed> $params
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row.
     *
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Fetch all rows.
     *
     * @param array<int|string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Get the last inserted ID.
     */
    public static function lastInsertId(): string
    {
        return self::connect()->lastInsertId();
    }

    /**
     * Test connection with given credentials (used by setup wizard).
     *
     * @return array{success: bool, error: string}
     */
    public static function testConnection(string $host, string $port, string $name, string $user, string $pass): array
    {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            return ['success' => true, 'error' => ''];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Import SQL schema file.
     */
    public static function importSchema(string $schemaPath): bool
    {
        if (!file_exists($schemaPath)) {
            return false;
        }

        $sql = file_get_contents($schemaPath);
        if ($sql === false) {
            return false;
        }

        try {
            self::connect()->exec($sql);
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    /**
     * Reset the connection (used after setup wizard writes .env).
     */
    public static function reset(): void
    {
        self::$pdo = null;
    }
}
