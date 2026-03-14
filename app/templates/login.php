<?php
/**
 * LicenseRadar — Sign In
 */

use function LicenseRadar\{e, csrf_field, csrf_token};

$theme  = $_SESSION['theme'] ?? 'dark';
$isDark = $theme === 'dark';
$csrfToken = csrf_token();

$pageTitle = 'Sign In';
ob_start();
?>

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm space-y-8">

        <!-- Logo + Theme Toggle -->
        <div class="text-center space-y-3">
            <div class="flex items-center justify-center gap-3">
                <div class="logo-box">
                    <svg width="24" height="24" viewBox="0 0 32 32" fill="none" aria-hidden="true" focusable="false">
                        <circle cx="16" cy="16" r="14" stroke="url(#gl)" stroke-width="2.5" fill="none"/>
                        <circle cx="16" cy="16" r="8" stroke="url(#gl)" stroke-width="2" fill="none" opacity=".5"/>
                        <circle cx="16" cy="16" r="3" fill="#38bdf8"/>
                        <defs><linearGradient id="gl" x1="4" y1="4" x2="28" y2="28"><stop stop-color="#38bdf8"/><stop offset="1" stop-color="#a78bfa"/></linearGradient></defs>
                    </svg>
                </div>
                <!-- Theme toggle next to logo -->
                <button type="button"
                        class="icon-btn theme-toggle"
                        id="login-theme-toggle"
                        aria-label="Switch to <?= $isDark ? 'light' : 'dark' ?> mode"
                        title="Switch to <?= $isDark ? 'light' : 'dark' ?> mode"
                        data-csrf="<?= e($csrfToken) ?>">
                    <?php if ($isDark): ?>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                    <?php endif; ?>
                </button>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-heading">Sign in to LicenseRadar</h1>
                <p class="text-sm text-muted mt-1">Enter your credentials to continue</p>
            </div>
        </div>

        <!-- Login Form -->
        <form method="POST" action="?route=login" class="space-y-4" aria-label="Sign in form">
            <?= csrf_field() ?>

            <!-- Username -->
            <div class="space-y-1">
                <label for="username" class="block text-xs font-medium text-label">Username or Email</label>
                <input type="text" id="username" name="username" required autofocus autocomplete="username"
                       class="input-field" placeholder="admin" aria-required="true">
            </div>

            <!-- Password with eye toggle -->
            <div class="space-y-1">
                <label for="password" class="block text-xs font-medium text-label">Password</label>
                <div class="input-password-wrap">
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           class="input-field" placeholder="Enter your password" aria-required="true">
                    <button type="button" class="eye-toggle" aria-label="Show password" data-target="password">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <!-- Forgot Password -->
            <div class="text-right">
                <span class="text-xs text-dimmed">Forgot password? Contact your administrator.</span>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-primary w-full" aria-label="Sign in to your account">
                Sign In
            </button>
        </form>

        <p class="text-center text-xs text-dimmed">
            Free & open-source · <a href="https://github.com/boopathirbk/licenseradar" target="_blank" rel="noopener noreferrer" class="underline" aria-label="View source code on GitHub">View source</a>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
