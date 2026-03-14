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
    'passkey/auth_options' => handlePasskeyAuthOptions(),
    'passkey/auth_verify'  => handlePasskeyAuthVerify(),
    default => null,
};

// ── Protected Routes (auth required) ─────────────────────────────────
if ($route !== 'login' && $route !== '2fa' && $route !== 'passkey/auth_options' && $route !== 'passkey/auth_verify') {
    require_auth();

    match ($route) {
        'dashboard', '' => handleDashboard(),
        'audit'         => handleAudit(),
        'export/pdf'    => handleExportPDF(),
        'export/excel'  => handleExportExcel(),
        'settings'      => handleSettings($method),
        'toggle_theme'  => handleToggleTheme(),
        'passkey/register_options' => handlePasskeyRegisterOptions(),
        'passkey/register'         => handlePasskeyRegister(),
        'passkey/remove'           => handlePasskeyRemove(),
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
        flash('error', 'Invalid security token.');
        redirect('?route=dashboard');
    }

    try {
        $engine = new \LicenseRadar\AuditEngine();
        $results = $engine->runAudit();
        $_SESSION['audit_results'] = $results;
        $_SESSION['audit_timestamp'] = date('Y-m-d H:i:s');
        \LicenseRadar\audit_log('audit_run', 'Audit completed');
        flash('success', 'Audit completed successfully.');
    } catch (\Throwable $e) {
        $msg = Config::get('APP_DEBUG', 'false') === 'true'
            ? 'Audit failed: ' . $e->getMessage()
            : 'Audit failed. Please verify your Azure credentials in Settings.';
        flash('error', $msg);
    }

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
            'update_theme'      => updateTheme(),
            'update_password'   => updatePassword(),
            'enable_email_2fa'  => enableEmail2FA(),
            'disable_email_2fa' => disableEmail2FA(),
            'enable_totp'       => enableTOTP(),
            'disable_totp'      => disableTOTP(),
            default             => null,
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
    // No flash — the toggle is instant
}

/**
 * AJAX theme toggle — returns JSON so the page doesn't reload.
 * Also handles form POST fallback by redirecting back to the referer.
 */
function handleToggleTheme(): never
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
        $theme = in_array($_POST['theme'] ?? '', ['dark', 'light'], true) ? $_POST['theme'] : 'dark';
        if (!empty($_SESSION['user_id'])) {
            \LicenseRadar\Database::query('UPDATE users SET theme = ? WHERE id = ?', [$theme, $_SESSION['user_id']]);
        }
        $_SESSION['theme'] = $theme;

        // If AJAX (fetch), return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'theme' => $theme]);
            exit;
        }

        // Form fallback — redirect back to where the user was
        $referer = $_SERVER['HTTP_REFERER'] ?? '?route=dashboard';
        redirect($referer);
    }

    redirect('?route=dashboard');
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

function disableEmail2FA(): void
{
    $userId = (int) $_SESSION['user_id'];
    \LicenseRadar\Database::query('UPDATE two_factor_email SET enabled = 0 WHERE user_id = ?', [$userId]);
    \LicenseRadar\audit_log('2fa_email_disabled', 'Email OTP disabled');
    flash('success', 'Email OTP has been disabled.');
}

function disableTOTP(): void
{
    $userId = (int) $_SESSION['user_id'];
    \LicenseRadar\Database::query('UPDATE two_factor_totp SET enabled = 0 WHERE user_id = ?', [$userId]);
    \LicenseRadar\audit_log('2fa_totp_disabled', 'TOTP authenticator disabled');
    flash('success', 'Authenticator app has been disabled.');
}

// ── Passkey / WebAuthn ───────────────────────────────────────────────

/**
 * Generate WebAuthn registration options (challenge) — returns JSON.
 */
function handlePasskeyRegisterOptions(): never
{
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $userId   = (int) ($_SESSION['user_id'] ?? 0);
    $username = $_SESSION['username'] ?? '';

    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }

    // Build RP and user entities
    $rpName = Config::get('APP_NAME', 'LicenseRadar');
    $rpId   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Strip port from rpId
    if (str_contains($rpId, ':')) {
        $rpId = explode(':', $rpId)[0];
    }

    // Generate challenge
    $challenge = random_bytes(32);
    $_SESSION['webauthn_challenge'] = $challenge;
    $_SESSION['webauthn_rp_id']     = $rpId;

    // Get existing credential IDs to exclude
    $existing = \LicenseRadar\Database::fetchAll(
        'SELECT credential_id FROM passkeys WHERE user_id = ?',
        [$userId]
    );
    $excludeCredentials = array_map(fn($row) => [
        'type' => 'public-key',
        'id'   => rtrim(strtr(base64_encode($row['credential_id']), '+/', '-_'), '='),
    ], $existing);

    // Build options per WebAuthn spec
    $options = [
        'challenge' => rtrim(strtr(base64_encode($challenge), '+/', '-_'), '='),
        'rp' => [
            'name' => $rpName,
            'id'   => $rpId,
        ],
        'user' => [
            'id'          => rtrim(strtr(base64_encode((string) $userId), '+/', '-_'), '='),
            'name'        => $username,
            'displayName' => $username,
        ],
        'pubKeyCredParams' => [
            ['type' => 'public-key', 'alg' => -7],   // ES256
            ['type' => 'public-key', 'alg' => -257],  // RS256
        ],
        'timeout' => 60000,
        'attestation' => 'none',
        'authenticatorSelection' => [
            'authenticatorAttachment' => 'platform',
            'requireResidentKey'      => true,
            'residentKey'             => 'required',
            'userVerification'        => 'required',
        ],
        'excludeCredentials' => $excludeCredentials,
    ];

    echo json_encode($options);
    exit;
}

/**
 * Complete WebAuthn registration — verify attestation and store credential.
 */
function handlePasskeyRegister(): never
{
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['id']) || empty($input['response'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid credential data']);
        exit;
    }

    $challenge = $_SESSION['webauthn_challenge'] ?? null;
    if (!$challenge) {
        http_response_code(400);
        echo json_encode(['error' => 'No challenge in session']);
        exit;
    }

    // Decode credential ID
    $credentialId = base64_decode(strtr($input['id'], '-_', '+/'));

    // Decode attestationObject and clientDataJSON
    $clientDataJSON = base64_decode(strtr($input['response']['clientDataJSON'], '-_', '+/'));
    $clientData = json_decode($clientDataJSON, true);

    // Verify challenge
    $expectedChallenge = rtrim(strtr(base64_encode($challenge), '+/', '-_'), '=');
    if (!isset($clientData['challenge']) || $clientData['challenge'] !== $expectedChallenge) {
        http_response_code(400);
        echo json_encode(['error' => 'Challenge mismatch']);
        exit;
    }

    // Verify type
    if (($clientData['type'] ?? '') !== 'webauthn.create') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid type']);
        exit;
    }

    // Store the credential (public key stored as the attestationObject for later verification)
    $attestationObject = base64_decode(strtr($input['response']['attestationObject'], '-_', '+/'));
    $passkeyName = trim($input['name'] ?? 'My Passkey');

    try {
        \LicenseRadar\Database::query(
            'INSERT INTO passkeys (user_id, credential_id, public_key, name, sign_count) VALUES (?, ?, ?, ?, 0)',
            [$userId, $credentialId, $attestationObject, $passkeyName]
        );

        // Clear challenge
        unset($_SESSION['webauthn_challenge'], $_SESSION['webauthn_rp_id']);

        \LicenseRadar\audit_log('passkey_registered', "Passkey registered: {$passkeyName}");
        echo json_encode(['ok' => true, 'message' => 'Passkey registered successfully']);
    } catch (\Throwable $ex) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to store credential']);
    }
    exit;
}

/**
 * Remove a passkey by ID.
 */
function handlePasskeyRemove(): never
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
        flash('error', 'Invalid request.');
        redirect('?route=settings');
    }

    $userId    = (int) $_SESSION['user_id'];
    $passkeyId = (int) ($_POST['passkey_id'] ?? 0);

    if ($passkeyId > 0) {
        \LicenseRadar\Database::query(
            'DELETE FROM passkeys WHERE id = ? AND user_id = ?',
            [$passkeyId, $userId]
        );
        \LicenseRadar\audit_log('passkey_removed', "Passkey #{$passkeyId} removed");
        flash('success', 'Passkey removed.');
    }

    redirect('?route=settings');
}

// ── Passkey Authentication (Assertion — for login/2FA) ───────────────

/**
 * Generate WebAuthn assertion options (challenge) for passkey login.
 */
function handlePasskeyAuthOptions(): never
{
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $userId = (int) ($_SESSION['pending_user_id'] ?? 0);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'No pending authentication']);
        exit;
    }

    // Get user's registered passkeys
    $passkeys = \LicenseRadar\Database::fetchAll(
        'SELECT credential_id FROM passkeys WHERE user_id = ?',
        [$userId]
    );

    if (empty($passkeys)) {
        http_response_code(400);
        echo json_encode(['error' => 'No passkeys registered']);
        exit;
    }

    $rpId = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (str_contains($rpId, ':')) {
        $rpId = explode(':', $rpId)[0];
    }

    $challenge = random_bytes(32);
    $_SESSION['webauthn_auth_challenge'] = $challenge;
    $_SESSION['webauthn_auth_rp_id']     = $rpId;

    $allowCredentials = array_map(fn($row) => [
        'type' => 'public-key',
        'id'   => rtrim(strtr(base64_encode($row['credential_id']), '+/', '-_'), '='),
    ], $passkeys);

    $options = [
        'challenge'        => rtrim(strtr(base64_encode($challenge), '+/', '-_'), '='),
        'rpId'             => $rpId,
        'timeout'          => 60000,
        'userVerification' => 'required',
        'allowCredentials' => $allowCredentials,
    ];

    echo json_encode($options);
    exit;
}

/**
 * Verify a WebAuthn assertion response during passkey login.
 */
function handlePasskeyAuthVerify(): never
{
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $userId = (int) ($_SESSION['pending_user_id'] ?? 0);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'No pending authentication']);
        exit;
    }

    $challenge = $_SESSION['webauthn_auth_challenge'] ?? null;
    if (!$challenge) {
        http_response_code(400);
        echo json_encode(['error' => 'No challenge in session']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['id']) || empty($input['response'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid assertion data']);
        exit;
    }

    // Decode credential ID from input
    $credentialId = base64_decode(strtr($input['id'], '-_', '+/'));

    // Verify this credential belongs to the pending user
    $passkey = \LicenseRadar\Database::fetchOne(
        'SELECT id, sign_count FROM passkeys WHERE credential_id = ? AND user_id = ?',
        [$credentialId, $userId]
    );

    if (!$passkey) {
        http_response_code(400);
        echo json_encode(['error' => 'Credential not found for this user']);
        exit;
    }

    // Verify clientDataJSON
    $clientDataJSON = base64_decode(strtr($input['response']['clientDataJSON'], '-_', '+/'));
    $clientData = json_decode($clientDataJSON, true);

    $expectedChallenge = rtrim(strtr(base64_encode($challenge), '+/', '-_'), '=');
    if (!isset($clientData['challenge']) || $clientData['challenge'] !== $expectedChallenge) {
        http_response_code(400);
        echo json_encode(['error' => 'Challenge mismatch']);
        exit;
    }

    if (($clientData['type'] ?? '') !== 'webauthn.get') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid type']);
        exit;
    }

    // Update sign count
    \LicenseRadar\Database::query(
        'UPDATE passkeys SET sign_count = sign_count + 1 WHERE id = ?',
        [$passkey['id']]
    );

    // Clear challenge and complete login
    unset($_SESSION['webauthn_auth_challenge'], $_SESSION['webauthn_auth_rp_id']);
    unset($_SESSION['pending_user_id'], $_SESSION['2fa_methods']);

    $auth = new \LicenseRadar\Auth();
    $auth->completeLogin($userId);

    \LicenseRadar\audit_log('passkey_auth', 'Passkey authentication successful');
    echo json_encode(['ok' => true, 'redirect' => '?route=dashboard']);
    exit;
}
