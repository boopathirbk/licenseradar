<?php
/**
 * LicenseRadar — Layout Template
 * Main HTML shell with header, navigation, footer, and flash messages.
 *
 * Variables: $pageTitle (string), $content (string — rendered by ob_start/ob_get_clean in page templates)
 */

use function LicenseRadar\{e, csrf_field, current_route, is_route, is_authenticated, get_flashes, base_url};

$theme      = $_SESSION['theme'] ?? 'dark';
$isDark     = $theme === 'dark';
$username   = $_SESSION['username'] ?? '';
$flashes    = get_flashes();
$appName    = LicenseRadar\Config::get('APP_NAME', 'LicenseRadar');
?>
<!DOCTYPE html>
<html lang="en" class="<?= e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle ?? 'Dashboard') ?> — <?= e($appName) ?></title>
    <link rel="preload" href="assets/fonts/Geist-Variable.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js" defer></script>
    <script src="assets/js/app.js" defer></script>
</head>
<body>

    <!-- Skip Link -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <?php if (is_authenticated()): ?>
    <!-- Header -->
    <header class="sticky top-0 z-50 <?= $isDark ? 'frosted-header-dark' : 'frosted-header-light' ?>">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">

            <!-- Logo -->
            <a href="?route=dashboard" class="flex items-center gap-2 font-semibold tracking-tight text-sm <?= $isDark ? 'text-white' : 'text-zinc-900' ?>">
                <svg width="24" height="24" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                    <circle cx="16" cy="16" r="14" stroke="url(#g)" stroke-width="2.5" fill="none"/>
                    <circle cx="16" cy="16" r="8" stroke="url(#g)" stroke-width="2" fill="none" opacity=".5"/>
                    <circle cx="16" cy="16" r="3" fill="#38bdf8"/>
                    <defs><linearGradient id="g" x1="4" y1="4" x2="28" y2="28"><stop stop-color="#38bdf8"/><stop offset="1" stop-color="#a78bfa"/></linearGradient></defs>
                </svg>
                <?= e($appName) ?>
            </a>

            <!-- Navigation -->
            <nav class="flex items-center gap-1" aria-label="Main navigation">
                <a href="?route=dashboard" class="nav-link <?= is_route('dashboard') || is_route('') ? 'nav-active' : '' ?>" <?= is_route('dashboard') ? 'aria-current="page"' : '' ?>>
                    Dashboard
                </a>
                <a href="?route=settings" class="nav-link <?= is_route('settings') ? 'nav-active' : '' ?>" <?= is_route('settings') ? 'aria-current="page"' : '' ?>>
                    Settings
                </a>
            </nav>

            <!-- Actions -->
            <div class="flex items-center gap-1">
                <span class="text-xs text-zinc-500 hidden sm:inline" style="margin-right:4px"><?= e($username) ?></span>

                <!-- Theme Toggle -->
                <form method="POST" action="?route=settings" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_theme">
                    <input type="hidden" name="theme" value="<?= $isDark ? 'light' : 'dark' ?>">
                    <button type="submit" class="icon-btn theme-toggle" aria-label="Switch to <?= $isDark ? 'light' : 'dark' ?> mode" title="Switch to <?= $isDark ? 'light' : 'dark' ?> mode">
                        <?php if ($isDark): ?>
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                        <?php endif; ?>
                    </button>
                </form>

                <!-- Logout -->
                <a href="?route=logout" class="icon-btn logout" aria-label="Logout" title="Logout">
                    <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                </a>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <!-- Flash Messages -->
    <?php if (!empty($flashes)): ?>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 mt-2 space-y-2" role="alert">
        <?php foreach ($flashes as $flash): ?>
        <div class="flash-<?= e($flash['type']) ?> rounded-lg px-4 py-3 text-sm flex items-center gap-2">
            <?php if ($flash['type'] === 'error'): ?>
                <svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <?php elseif ($flash['type'] === 'success'): ?>
                <svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
            <?php endif; ?>
            <?= e($flash['message']) ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main id="main-content" class="flex-1">
        <?= $content ?? '' ?>
    </main>

    <!-- Footer -->
    <?php if (is_authenticated()): ?>
    <footer class="border-t <?= $isDark ? 'border-zinc-800' : 'border-zinc-200' ?> mt-auto">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <span class="text-xs <?= $isDark ? 'text-zinc-600' : 'text-zinc-400' ?>">
                © <?= date('Y') ?> <?= e($appName) ?> · Free & Open Source
            </span>
            <div class="flex items-center gap-4 text-xs <?= $isDark ? 'text-zinc-600' : 'text-zinc-400' ?>">
                <a href="https://github.com/boopathirbk/licenseradar" target="_blank" rel="noopener noreferrer" class="underline">GitHub</a>
                <a href="https://boopathirbk.github.io/licenseradar/" target="_blank" rel="noopener noreferrer" class="underline">Docs</a>
            </div>
        </div>
    </footer>
    <?php endif; ?>

</body>
</html>
