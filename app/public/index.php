<?php
/**
 * LicenseRadar — Front Controller & Router
 * All HTTP requests are routed through this file.
 */

declare(strict_types=1);

// ── Bootstrap ────────────────────────────────────────────────────────
require_once __DIR__ . '/../src/bootstrap.php';

use LicenseRadar\Config;
use function LicenseRadar\{is_authenticated, require_auth, redirect, current_route, csrf_verify, flash};

// ── Redirect to setup if not installed ───────────────────────────────
if (!Config::isInstalled()) {
    if (basename($_SERVER['SCRIPT_NAME']) !== 'setup.php') {
        redirect('setup.php');
    }
}

// ── Route Handling ───────────────────────────────────────────────────
$route  = current_route();
$method = $_SERVER['REQUEST_METHOD'];

// ── Public Routes (no auth required) ─────────────────────────────────
match ($route) {
    'login' => handleLogin($method),
    '2fa'   => handle2FA($method),
    default => null,
};

// ── Protected Routes (auth required) ─────────────────────────────────
if ($route !== 'login' && $route !== '2fa') {
    require_auth();

    match ($route) {
        'dashboard', '' => handleDashboard(),
        'audit'         => handleAudit(),
        'export/pdf'    => handleExportPDF(),
        'export/excel'  => handleExportExcel(),
        'settings'      => handleSettings($method),
        'logout'        => handleLogout(),
        default         => handleDashboard(),
    };
}

// ═══════════════════════════════════════════════════════════════════════
// Route Handlers
// ═══════════════════════════════════════════════════════════════════════

function handleLogin(string $method): never
{
    if (is_authenticated()) {
        redirect('?route=dashboard');
    }

    if ($method === 'POST') {
        if (!csrf_verify()) {
            flash('error', 'Invalid security token. Please try again.');
            redirect('?route=login');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $auth = new \LicenseRadar\Auth();
        $result = $auth->attemptLogin($username, $password);

        if ($result['success']) {
            if ($result['requires_2fa']) {
                $_SESSION['pending_user_id'] = $result['user_id'];
                $_SESSION['2fa_methods']     = $result['2fa_methods'];
                redirect('?route=2fa');
            }
            // No 2FA required — complete login
            $auth->completeLogin($result['user_id']);
            redirect('?route=dashboard');
        }

        flash('error', $result['error']);
        redirect('?route=login');
    }

    require __DIR__ . '/../templates/login.php';
    exit;
}

function handle2FA(string $method): never
{
    if (empty($_SESSION['pending_user_id'])) {
        redirect('?route=login');
    }

    if ($method === 'POST') {
        if (!csrf_verify()) {
            flash('error', 'Invalid security token.');
            redirect('?route=2fa');
        }

        $auth   = new \LicenseRadar\Auth();
        $code   = trim($_POST['code'] ?? '');
        $method2fa = $_POST['method'] ?? 'email';

        $valid = $auth->verify2FA((int) $_SESSION['pending_user_id'], $method2fa, $code);

        if ($valid) {
            $userId = (int) $_SESSION['pending_user_id'];
            unset($_SESSION['pending_user_id'], $_SESSION['2fa_methods']);
            $auth->completeLogin($userId);
            redirect('?route=dashboard');
        }

        flash('error', 'Invalid verification code.');
        redirect('?route=2fa');
    }

    require __DIR__ . '/../templates/2fa.php';
    exit;
}

function handleDashboard(): never
{
    require __DIR__ . '/../templates/dashboard.php';
    exit;
}

function handleAudit(): never
{
    if (!csrf_verify() && $_SERVER['REQUEST_METHOD'] === 'POST') {
        flash('error', 'Invalid security token.');
        redirect('?route=dashboard');
    }

    $engine = new \LicenseRadar\AuditEngine();
    $results = $engine->runAudit();
    $_SESSION['audit_results'] = $results;
    $_SESSION['audit_timestamp'] = date('Y-m-d H:i:s');
    \LicenseRadar\audit_log('audit_run', 'Audit completed');

    flash('success', 'Audit completed successfully.');
    redirect('?route=dashboard');
}

function handleExportPDF(): never
{
    require_auth();
    if (empty($_SESSION['audit_results'])) {
        flash('error', 'Please run an audit first.');
        redirect('?route=dashboard');
    }

    $exporter = new \LicenseRadar\ReportExporter();
    $exporter->exportPDF($_SESSION['audit_results']);
    exit;
}

function handleExportExcel(): never
{
    require_auth();
    if (empty($_SESSION['audit_results'])) {
        flash('error', 'Please run an audit first.');
        redirect('?route=dashboard');
    }

    $exporter = new \LicenseRadar\ReportExporter();
    $exporter->exportExcel($_SESSION['audit_results']);
    exit;
}

function handleSettings(string $method): never
{
    if ($method === 'POST' && csrf_verify()) {
        $action = $_POST['action'] ?? '';

        match ($action) {
            'update_theme'    => updateTheme(),
            'update_password' => updatePassword(),
            'enable_email_2fa'=> enableEmail2FA(),
            'enable_totp'     => enableTOTP(),
            default           => null,
        };
    }

    require __DIR__ . '/../templates/settings.php';
    exit;
}

function handleLogout(): never
{
    \LicenseRadar\audit_log('logout', 'User logged out');
    session_unset();
    session_destroy();
    redirect('?route=login');
}

// ── Settings sub-handlers ────────────────────────────────────────────

function updateTheme(): void
{
    $theme = in_array($_POST['theme'] ?? '', ['dark', 'light'], true) ? $_POST['theme'] : 'dark';
    \LicenseRadar\Database::query('UPDATE users SET theme = ? WHERE id = ?', [$theme, $_SESSION['user_id']]);
    $_SESSION['theme'] = $theme;
    flash('success', 'Theme updated.');
}

function updatePassword(): void
{
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        flash('error', 'New passwords do not match.');
        return;
    }

    if (strlen($new) < 12) {
        flash('error', 'Password must be at least 12 characters.');
        return;
    }

    $user = \LicenseRadar\Database::fetchOne('SELECT password_hash FROM users WHERE id = ?', [$_SESSION['user_id']]);
    if (!$user || !password_verify($current, $user['password_hash'])) {
        flash('error', 'Current password is incorrect.');
        return;
    }

    $hash = password_hash($new, PASSWORD_ARGON2ID);
    \LicenseRadar\Database::query('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $_SESSION['user_id']]);
    \LicenseRadar\audit_log('password_changed', 'Password updated');
    flash('success', 'Password updated successfully.');
}

function enableEmail2FA(): void
{
    $userId = (int) $_SESSION['user_id'];
    $existing = \LicenseRadar\Database::fetchOne('SELECT id FROM two_factor_email WHERE user_id = ?', [$userId]);
    if ($existing) {
        \LicenseRadar\Database::query('UPDATE two_factor_email SET enabled = 1 WHERE user_id = ?', [$userId]);
    } else {
        \LicenseRadar\Database::query('INSERT INTO two_factor_email (user_id, enabled) VALUES (?, 1)', [$userId]);
    }
    \LicenseRadar\audit_log('2fa_email_enabled', 'Email OTP enabled');
    flash('success', 'Email OTP enabled.');
}

function enableTOTP(): void
{
    $userId = (int) $_SESSION['user_id'];
    $secret = $_POST['totp_secret'] ?? '';
    $code   = $_POST['totp_code'] ?? '';

    if (empty($secret) || empty($code)) {
        flash('error', 'Please provide the TOTP secret and verification code.');
        return;
    }

    // Verify the code before saving
    $otp = \OTPHP\TOTP::createFromSecret($secret);
    if (!$otp->verify($code)) {
        flash('error', 'Invalid TOTP code. Please try again.');
        return;
    }

    $existing = \LicenseRadar\Database::fetchOne('SELECT id FROM two_factor_totp WHERE user_id = ?', [$userId]);
    if ($existing) {
        \LicenseRadar\Database::query('UPDATE two_factor_totp SET secret = ?, enabled = 1 WHERE user_id = ?', [$secret, $userId]);
    } else {
        \LicenseRadar\Database::query('INSERT INTO two_factor_totp (user_id, secret, enabled) VALUES (?, ?, 1)', [$userId, $secret]);
    }
    \LicenseRadar\audit_log('2fa_totp_enabled', 'TOTP authenticator enabled');
    flash('success', 'TOTP authenticator enabled.');
}
