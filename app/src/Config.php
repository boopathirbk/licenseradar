<?php
/**
 * LicenseRadar — Configuration Loader
 * Reads .env file and defines application constants.
 */

declare(strict_types=1);

namespace LicenseRadar;

final class Config
{
    /** @var array<string, string> */
    private static array $values = [];
    private static bool $loaded = false;

    /**
     * Load .env file from the app root directory.
     */
    public static function load(string $envPath): void
    {
        if (self::$loaded) {
            return;
        }

        if (!file_exists($envPath)) {
            return; // Setup wizard will handle missing .env
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comments
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key   = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Remove surrounding quotes
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            self::$values[$key] = $value;
        }

        self::$loaded = true;
    }

    /**
     * Force reload .env (used by setup wizard after writing new .env).
     */
    public static function reload(string $envPath): void
    {
        self::$loaded = false;
        self::$values = [];
        self::load($envPath);
    }

    /**
     * Get a configuration value.
     */
    public static function get(string $key, string $default = ''): string
    {
        return self::$values[$key] ?? $_ENV[$key] ?? $default;
    }

    /**
     * Get a configuration value as integer.
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key);
        return $value !== '' ? (int) $value : $default;
    }

    /**
     * Get a boolean configuration value.
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = strtolower(self::get($key));
        if ($value === '') {
            return $default;
        }
        return in_array($value, ['true', '1', 'yes', 'on'], true);
    }

    /**
     * Check if .env has been loaded (i.e. app is installed).
     */
    public static function isInstalled(): bool
    {
        return self::$loaded && self::get('DB_NAME') !== '';
    }
}
