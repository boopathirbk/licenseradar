<?php
/**
 * LicenseRadar — Helper Functions
 * CSRF protection, sanitization, redirects, flash messages, and utilities.
 */

declare(strict_types=1);

namespace LicenseRadar;

/**
 * Generate or retrieve the CSRF token for the current session.
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Output a hidden CSRF input field.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify the CSRF token from a POST request.
 */
function csrf_verify(): bool
{
    $token = $_POST['_csrf_token'] ?? '';
    if (empty($token) || empty($_SESSION['_csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['_csrf_token'], $token);
}

/**
 * Sanitize a string for HTML output.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redirect to a URL and exit.
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Set a flash message in the session.
 */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear all flash messages.
 *
 * @return array<int, array{type: string, message: string}>
 */
function get_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $flashes;
}

/**
 * Check if the current user is authenticated.
 */
function is_authenticated(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['authenticated']);
}

/**
 * Get the current user's ID.
 */
function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Require authentication. Redirects to login if not authenticated.
 */
function require_auth(): void
{
    if (!is_authenticated()) {
        redirect('?route=login');
    }
}

/**
 * Get the base URL for the application.
 */
function base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return "{$scheme}://{$host}{$path}";
}

/**
 * Get the current route.
 */
function current_route(): string
{
    return $_GET['route'] ?? 'dashboard';
}

/**
 * Check if the given route is the current one.
 */
function is_route(string $route): bool
{
    return current_route() === $route;
}

/**
 * Format a date relative to now (e.g., "3 days ago").
 */
function time_ago(string $datetime): string
{
    $now  = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    $then = new \DateTimeImmutable($datetime, new \DateTimeZone('UTC'));
    $diff = $now->diff($then);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

/**
 * Format currency with symbol.
 */
function format_currency(float $amount, string $currency = 'USD'): string
{
    $symbol = $currency === 'INR' ? '₹' : '$';
    return $symbol . number_format($amount, 2);
}

/**
 * Log an action to the audit_log table.
 */
function audit_log(string $action, ?string $detail = null): void
{
    try {
        Database::query(
            'INSERT INTO audit_log (user_id, action, detail, ip_address) VALUES (?, ?, ?, ?)',
            [current_user_id(), $action, $detail, $_SERVER['REMOTE_ADDR'] ?? null]
        );
    } catch (\Throwable) {
        // Silently fail — audit log should never break the app
    }
}
