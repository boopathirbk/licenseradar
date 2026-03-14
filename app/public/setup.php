<?php
/**
 * LicenseRadar — Install Wizard
 * 5-step WordPress-style setup: Requirements → Database → Azure → Admin → Complete
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/helpers.php';

use LicenseRadar\{Config, Database};
use function LicenseRadar\{e, csrf_field, csrf_verify, flash, get_flashes, redirect};

// Don't start full session with security headers for setup — simpler session
if (session_status() === PHP_SESSION_NONE) {
    session_name('LR_SETUP');
    session_start();
}

// If already installed, redirect to app
Config::load(dirname(__DIR__) . '/.env');
if (Config::isInstalled()) {
    header('Location: index.php');
    exit;
}

$step = (int) ($_GET['step'] ?? ($_SESSION['setup_step'] ?? 1));
$step = max(1, min(5, $step));
$method = $_SERVER['REQUEST_METHOD'];
$flashes = get_flashes();

// Handle POST submissions
if ($method === 'POST') {
    match ($step) {
        1 => handleStep1(),
        2 => handleStep2(),
        3 => handleStep3(),
        4 => handleStep4(),
        default => null,
    };
}

function handleStep1(): void {
    $_SESSION['setup_step'] = 2;
    redirect('setup.php?step=2');
}

function handleStep2(): void {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $port = trim($_POST['db_port'] ?? '3306');
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = $_POST['db_pass'] ?? '';

    if (empty($name) || empty($user)) {
        flash('error', 'Database name and user are required.');
        redirect('setup.php?step=2');
    }

    $test = Database::testConnection($host, $port, $name, $user, $pass);
    if (!$test['success']) {
        flash('error', 'Connection failed: ' . $test['error']);
        redirect('setup.php?step=2');
    }

    $_SESSION['setup_db'] = compact('host', 'port', 'name', 'user', 'pass');
    $_SESSION['setup_step'] = 3;
    redirect('setup.php?step=3');
}

function handleStep3(): void {
    $tenantId     = trim($_POST['tenant_id'] ?? '');
    $clientId     = trim($_POST['client_id'] ?? '');
    $clientSecret = trim($_POST['client_secret'] ?? '');

    if (empty($tenantId) || empty($clientId) || empty($clientSecret)) {
        flash('error', 'All Azure fields are required.');
        redirect('setup.php?step=3');
    }

    $_SESSION['setup_azure'] = compact('tenantId', 'clientId', 'clientSecret');
    $_SESSION['setup_step'] = 4;
    redirect('setup.php?step=4');
}

function handleStep4(): void {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        flash('error', 'All fields are required.');
        redirect('setup.php?step=4');
    }

    if (strlen($password) < 12) {
        flash('error', 'Password must be at least 12 characters.');
        redirect('setup.php?step=4');
    }

    if ($password !== $confirm) {
        flash('error', 'Passwords do not match.');
        redirect('setup.php?step=4');
    }

    // Write .env file
    $db    = $_SESSION['setup_db'] ?? [];
    $azure = $_SESSION['setup_azure'] ?? [];
    $envContent = <<<ENV
    DB_HOST={$db['host']}
    DB_PORT={$db['port']}
    DB_NAME={$db['name']}
    DB_USER={$db['user']}
    DB_PASS={$db['pass']}

    AZURE_TENANT_ID={$azure['tenantId']}
    AZURE_CLIENT_ID={$azure['clientId']}
    AZURE_CLIENT_SECRET={$azure['clientSecret']}

    SMTP_HOST=
    SMTP_PORT=587
    SMTP_USER=
    SMTP_PASS=
    SMTP_FROM_NAME=LicenseRadar
    SMTP_FROM_EMAIL=

    APP_NAME=LicenseRadar
    APP_URL=
    APP_DEBUG=false

    SESSION_LIFETIME=1800
    SESSION_ABSOLUTE=28800
    RATE_LIMIT_ATTEMPTS=5
    RATE_LIMIT_WINDOW=900
    ENV;

    // Remove leading whitespace from heredoc lines
    $envContent = preg_replace('/^    /m', '', $envContent);
    $envPath = dirname(__DIR__) . '/.env';
    file_put_contents($envPath, $envContent);

    // Reload config and connect to DB
    try {
        Config::reload($envPath);
        Database::reset();

        // Import schema
        $schemaPath = dirname(__DIR__) . '/schema.sql';
        if (!Database::importSchema($schemaPath)) {
            flash('error', 'Failed to import database schema.');
            redirect('setup.php?step=4');
        }

        // Create admin user
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        Database::query(
            'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)',
            [$username, $email, $hash, 'admin']
        );

        // Clean up session
        unset($_SESSION['setup_db'], $_SESSION['setup_azure']);
        $_SESSION['setup_step'] = 5;
        redirect('setup.php?step=5');
    } catch (\Throwable $e) {
        flash('error', 'Installation failed: ' . $e->getMessage());
        redirect('setup.php?step=4');
    }
}

// Check PHP extensions — use function_exists as fallback for mbstring
$requirements = [
    ['PHP >= 8.2',    version_compare(PHP_VERSION, '8.2.0', '>=')],
    ['PDO MySQL',     extension_loaded('pdo_mysql')],
    ['cURL',          extension_loaded('curl')],
    ['mbstring',      extension_loaded('mbstring') || function_exists('mb_detect_encoding')],
    ['OpenSSL',       extension_loaded('openssl')],
    ['JSON',          extension_loaded('json') || function_exists('json_encode')],
    ['GD or Imagick', extension_loaded('gd') || extension_loaded('imagick')],
];
$allPassed = array_reduce($requirements, fn($carry, $r) => $carry && $r[1], true);
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="LicenseRadar Setup Wizard — Configure your Microsoft 365 license audit tool">
    <title>Setup Wizard — LicenseRadar</title>
    <link rel="preload" href="assets/fonts/Geist-Variable.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body style="display:flex;align-items:center;justify-content:center;padding:2rem 1rem">

<!-- Theme toggle (top-right corner) -->
<button type="button"
        class="icon-btn theme-toggle"
        id="setup-theme-toggle"
        aria-label="Switch to light mode"
        title="Switch to light mode"
        data-csrf="<?= e(\LicenseRadar\csrf_token()) ?>"
        style="position:fixed;top:16px;right:16px;z-index:100">
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
</button>

<div class="w-full max-w-lg space-y-8">

    <!-- Logo -->
    <div class="text-center space-y-3">
        <div class="mx-auto logo-box">
            <svg width="24" height="24" viewBox="0 0 32 32" fill="none" aria-hidden="true" focusable="false">
                <circle cx="16" cy="16" r="14" stroke="url(#gs)" stroke-width="2.5" fill="none"/>
                <circle cx="16" cy="16" r="8" stroke="url(#gs)" stroke-width="2" fill="none" opacity=".5"/>
                <circle cx="16" cy="16" r="3" fill="#38bdf8"/>
                <defs><linearGradient id="gs" x1="4" y1="4" x2="28" y2="28"><stop stop-color="#38bdf8"/><stop offset="1" stop-color="#a78bfa"/></linearGradient></defs>
            </svg>
        </div>
        <h1 class="setup-title">LicenseRadar Setup</h1>
    </div>

    <!-- Progress bar -->
    <div class="flex items-center gap-1">
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <div class="flex-1 rounded-full transition-colors" style="height:6px;background:<?= $i <= $step ? '#0ea5e9' : 'var(--progress-inactive, #27272a)' ?>"></div>
        <?php endfor; ?>
    </div>
    <p class="text-center text-xs text-zinc-500">Step <?= $step ?> of 5</p>

    <!-- Flash Messages -->
    <?php if (!empty($flashes)): ?>
    <div class="space-y-2" role="alert">
        <?php foreach ($flashes as $f): ?>
        <div class="flash-<?= e($f['type']) ?> rounded-lg px-4 py-3 text-sm"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Step Content -->
    <?php if ($step === 1): ?>
    <!-- Step 1: Requirements -->
    <div class="card space-y-4">
        <h2 class="card-title">System Requirements</h2>
        <div class="space-y-2">
            <?php foreach ($requirements as $r): ?>
            <div class="flex items-center justify-between text-sm">
                <span class="text-zinc-400"><?= e($r[0]) ?></span>
                <?php if ($r[1]): ?>
                    <span class="text-emerald-500 font-medium">✓ Passed</span>
                <?php else: ?>
                    <span class="text-rose-500 font-medium">✗ Missing</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($allPassed): ?>
        <form method="POST">
            <?= csrf_field() ?>
            <button type="submit" class="btn-primary w-full">Continue</button>
        </form>
        <?php else: ?>
        <p class="text-xs text-rose-400">Please install the missing PHP extensions before continuing.</p>
        <?php endif; ?>
    </div>

    <?php elseif ($step === 2): ?>
    <!-- Step 2: Database -->
    <div class="card space-y-4">
        <h2 class="card-title">Database Configuration</h2>
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-label">Host</label>
                    <input type="text" name="db_host" value="localhost" class="input-field" required>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-label">Port</label>
                    <input type="text" name="db_port" value="3306" class="input-field" required>
                </div>
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-label">Database Name</label>
                <input type="text" name="db_name" class="input-field" placeholder="licenseradar" required>
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-label">Username</label>
                <input type="text" name="db_user" class="input-field" placeholder="root" required>
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-label">Password</label>
                <input type="password" name="db_pass" class="input-field" placeholder="••••••">
            </div>
            <button type="submit" class="btn-primary w-full">Test & Continue</button>
        </form>
    </div>

    <?php elseif ($step === 3): ?>
    <!-- Step 3: Azure -->
    <div class="card space-y-4">
        <h2 class="card-title">Azure App Registration</h2>
        <p class="text-xs text-zinc-500">
            Register an app in <a href="https://portal.azure.com/#view/Microsoft_AAD_RegisteredApps/ApplicationsListBlade" target="_blank" class="text-sky-500 underline">Azure Portal</a>
            with API permissions: <code class="text-sky-400">User.Read.All</code>, <code class="text-sky-400">Directory.Read.All</code>, <code class="text-sky-400">Organization.Read.All</code> (Application type).
        </p>
        <form method="POST" class="space-y-4">
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-label">Tenant ID</label>
                <input type="text" name="tenant_id" class="input-field font-mono text-xs" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" required>
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-label">Client ID</label>
                <input type="text" name="client_id" class="input-field font-mono text-xs" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" required>
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-label">Client Secret</label>
                <input type="password" name="client_secret" class="input-field font-mono text-xs" required>
            </div>
            <button type="submit" class="btn-primary w-full">Continue</button>
        </form>
    </div>

    <?php elseif ($step === 4): ?>
    <!-- Step 4: Admin Account -->
    <div class="card space-y-4">
        <h2 class="card-title">Create Admin Account</h2>
        <form method="POST" class="space-y-4">
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-label">Username</label>
                <input type="text" name="username" class="input-field" placeholder="admin" required autocomplete="username">
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-label">Email</label>
                <input type="email" name="email" class="input-field" required autocomplete="email">
            </div>
            <div class="space-y-1">
                <label class="block text-xs font-medium text-label">Password</label>
                <div class="input-password-wrap">
                    <input type="password" id="setup_password" name="password" class="input-field" minlength="12" required autocomplete="new-password">
                    <button type="button" class="eye-toggle" aria-label="Show password" onclick="togglePw(this,'setup_password')">
                        <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <p class="text-xs text-zinc-600">Minimum 12 characters</p>
            </div>
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-400">Confirm Password</label>
                <div class="input-password-wrap">
                    <input type="password" id="setup_confirm" name="confirm_password" class="input-field" minlength="12" required autocomplete="new-password">
                    <button type="button" class="eye-toggle" aria-label="Show password" onclick="togglePw(this,'setup_confirm')">
                        <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-primary w-full">Create & Install</button>
        </form>
    </div>

    <?php elseif ($step === 5): ?>
    <!-- Step 5: Complete -->
    <div class="card text-center space-y-6 py-8">
        <div class="mx-auto empty-state-icon" style="background:rgba(16,185,129,0.1);border-color:rgba(16,185,129,0.2)">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" aria-hidden="true" focusable="false"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
        </div>
        <div>
            <h2 class="text-xl font-bold text-heading">Setup Complete!</h2>
            <p class="text-sm text-muted mt-2">LicenseRadar is ready. Sign in with your admin account.</p>
        </div>
        <a href="index.php?route=login" class="btn-primary inline-flex px-8">Go to Login</a>
    </div>
    <?php endif; ?>

</div>

<script>
function togglePw(btn, id) {
    var inp = document.getElementById(id);
    if (!inp) return;
    var isPw = inp.type === 'password';
    inp.type = isPw ? 'text' : 'password';
    btn.innerHTML = isPw
        ? '<svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
        : '<svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
}
</script>
</body>
</html>
