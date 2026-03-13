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
    Config::load($envPath);
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
}

// Check PHP extensions
$requirements = [
    ['PHP >= 8.2',    version_compare(PHP_VERSION, '8.2.0', '>=')],
    ['PDO MySQL',     extension_loaded('pdo_mysql')],
    ['cURL',          extension_loaded('curl')],
    ['mbstring',      extension_loaded('mbstring')],
    ['OpenSSL',       extension_loaded('openssl')],
    ['JSON',          extension_loaded('json')],
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
    <title>Setup — LicenseRadar</title>
    <link rel="preload" href="assets/fonts/Geist-Variable.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="bg-zinc-950 text-zinc-100 font-sans antialiased min-h-screen flex items-center justify-center px-4 py-8">

<div class="w-full max-w-lg space-y-8">

    <!-- Logo -->
    <div class="text-center space-y-3">
        <div class="mx-auto w-12 h-12 rounded-xl bg-zinc-900 border border-zinc-800 flex items-center justify-center">
            <svg width="24" height="24" viewBox="0 0 32 32" fill="none">
                <circle cx="16" cy="16" r="14" stroke="url(#gs)" stroke-width="2.5" fill="none"/>
                <circle cx="16" cy="16" r="8" stroke="url(#gs)" stroke-width="2" fill="none" opacity=".5"/>
                <circle cx="16" cy="16" r="3" fill="#38bdf8"/>
                <defs><linearGradient id="gs" x1="4" y1="4" x2="28" y2="28"><stop stop-color="#38bdf8"/><stop offset="1" stop-color="#a78bfa"/></linearGradient></defs>
            </svg>
        </div>
        <h1 class="text-xl font-bold tracking-tight text-white">LicenseRadar Setup</h1>
    </div>

    <!-- Progress bar -->
    <div class="flex items-center gap-1">
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <div class="flex-1 h-1.5 rounded-full <?= $i <= $step ? 'bg-sky-500' : 'bg-zinc-800' ?> transition-colors"></div>
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
                    <label class="block text-xs font-medium text-zinc-400">Host</label>
                    <input type="text" name="db_host" value="localhost" class="input-field" required>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-zinc-400">Port</label>
                    <input type="text" name="db_port" value="3306" class="input-field" required>
                </div>
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-zinc-400">Database Name</label>
                <input type="text" name="db_name" class="input-field" placeholder="licenseradar" required>
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-zinc-400">Username</label>
                <input type="text" name="db_user" class="input-field" placeholder="root" required>
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-zinc-400">Password</label>
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
                <label class="block text-xs font-medium text-zinc-400">Tenant ID</label>
                <input type="text" name="tenant_id" class="input-field font-mono text-xs" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" required>
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-zinc-400">Client ID</label>
                <input type="text" name="client_id" class="input-field font-mono text-xs" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" required>
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-zinc-400">Client Secret</label>
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
                <label class="block text-xs font-medium text-zinc-400">Username</label>
                <input type="text" name="username" class="input-field" placeholder="admin" required>
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-zinc-400">Email</label>
                <input type="email" name="email" class="input-field" required>
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-zinc-400">Password</label>
                <input type="password" name="password" class="input-field" minlength="12" required>
                <p class="text-xs text-zinc-600">Minimum 12 characters</p>
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-zinc-400">Confirm Password</label>
                <input type="password" name="confirm_password" class="input-field" minlength="12" required>
            </div>
            <button type="submit" class="btn-primary w-full">Create & Install</button>
        </form>
    </div>

    <?php elseif ($step === 5): ?>
    <!-- Step 5: Complete -->
    <div class="card text-center space-y-6 py-8">
        <div class="mx-auto w-16 h-16 rounded-2xl bg-emerald-500/10 flex items-center justify-center">
            <svg class="w-8 h-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
        </div>
        <div>
            <h2 class="text-xl font-bold text-white">Setup Complete!</h2>
            <p class="text-sm text-zinc-500 mt-2">LicenseRadar is ready. Sign in with your admin account.</p>
        </div>
        <a href="index.php?route=login" class="btn-primary inline-flex px-8">Go to Login</a>
    </div>
    <?php endif; ?>

</div>

</body>
</html>
