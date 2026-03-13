<?php
/**
 * LicenseRadar — Bootstrap
 * Initializes autoloader, config, session, security headers, and error handling.
 * Required by index.php and setup.php.
 */

declare(strict_types=1);

// ── Autoloader ───────────────────────────────────────────────────────
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/helpers.php';

use LicenseRadar\Config;

// ── Load Configuration ───────────────────────────────────────────────
Config::load(dirname(__DIR__) . '/.env');

// ── Error Handling ───────────────────────────────────────────────────
if (Config::getBool('APP_DEBUG')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ── Session Configuration ────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $secure   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $lifetime = Config::getInt('SESSION_LIFETIME', 1800);

    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) $lifetime);

    session_name('LR_SESSION');
    session_start();

    // ── Idle timeout ─────────────────────────────────────────────────
    if (isset($_SESSION['_last_activity'])) {
        if (time() - $_SESSION['_last_activity'] > $lifetime) {
            session_unset();
            session_destroy();
            session_start();
        }
    }
    $_SESSION['_last_activity'] = time();

    // ── Absolute timeout ─────────────────────────────────────────────
    $absoluteTimeout = Config::getInt('SESSION_ABSOLUTE', 28800);
    if (isset($_SESSION['_created_at'])) {
        if (time() - $_SESSION['_created_at'] > $absoluteTimeout) {
            session_unset();
            session_destroy();
            session_start();
        }
    } else {
        $_SESSION['_created_at'] = time();
    }
}

// ── Security Headers ─────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 0');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self' cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' data:; connect-src 'self'");

if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
