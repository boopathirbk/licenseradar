<?php
/**
 * LicenseRadar — Layout Template
 * Main shell: sticky header, flash messages, main content, sticky footer.
 *
 * Variables: $pageTitle, $content (from ob_start/ob_get_clean in page templates)
 */

use function LicenseRadar\{e, csrf_field, csrf_token, current_route, is_route, is_authenticated, get_flashes, base_url};

$theme      = $_SESSION['theme'] ?? 'dark';
$isDark     = $theme === 'dark';
$username   = $_SESSION['username'] ?? '';
$flashes    = get_flashes();
$appName    = LicenseRadar\Config::get('APP_NAME', 'LicenseRadar');
$csrfToken  = csrf_token();
?>
<!DOCTYPE html>
<html lang="en" class="<?= e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="<?= e($appName) ?> — Microsoft 365 license audit & cost-optimization dashboard">
    <title><?= e($pageTitle ?? 'Dashboard') ?> — <?= e($appName) ?></title>
    <link rel="preload" href="assets/fonts/Geist-Variable.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js" integrity="sha384-jb8JQMbMoBUzgWatfe6COACi2ljcDdZQ2OxczGA3bGNeWe+6DChMTBJemed7ZnvJ" crossorigin="anonymous" defer></script>
    <script src="assets/js/app.js" defer></script>
</head>
<body>

    <!-- Skip Link (WCAG 2.4.1) -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <?php if (is_authenticated()): ?>
    <!-- ── Header ──────────────────────────────────────────────────── -->
    <header class="sticky top-0 z-50 <?= $isDark ? 'frosted-header-dark' : 'frosted-header-light' ?>" role="banner">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">

            <!-- Logo -->
            <a href="?route=dashboard" class="flex items-center gap-2 font-semibold tracking-tight text-sm text-heading" aria-label="<?= e($appName) ?> — Go to Dashboard">
                <svg width="24" height="24" viewBox="0 0 32 32" fill="none" aria-hidden="true" focusable="false">
                    <circle cx="16" cy="16" r="14" stroke="url(#g)" stroke-width="2.5" fill="none"/>
                    <circle cx="16" cy="16" r="8" stroke="url(#g)" stroke-width="2" fill="none" opacity=".5"/>
                    <circle cx="16" cy="16" r="3" fill="#38bdf8"/>
                    <defs><linearGradient id="g" x1="4" y1="4" x2="28" y2="28"><stop stop-color="#38bdf8"/><stop offset="1" stop-color="#a78bfa"/></linearGradient></defs>
                </svg>
                <span><?= e($appName) ?></span>
            </a>

            <!-- Navigation -->
            <nav class="flex items-center gap-1" aria-label="Primary navigation">
                <a href="?route=dashboard"
                   class="nav-link <?= is_route('dashboard') || is_route('') ? 'nav-active' : '' ?>"
                   <?= is_route('dashboard') || is_route('') ? 'aria-current="page"' : '' ?>>
                    Dashboard
                </a>
                <a href="?route=settings"
                   class="nav-link <?= is_route('settings') ? 'nav-active' : '' ?>"
                   <?= is_route('settings') ? 'aria-current="page"' : '' ?>>
                    Settings
                </a>
            </nav>

            <!-- Actions -->
            <div class="flex items-center gap-1" role="toolbar" aria-label="Quick actions">
                <span class="text-xs text-zinc-500 hidden sm:inline" style="margin-right:4px" aria-label="Logged in as <?= e($username) ?>"><?= e($username) ?></span>

                <!-- Theme Toggle (JS inline — no page navigation) -->
                <button type="button"
                        id="header-theme-toggle"
                        class="icon-btn theme-toggle"
                        aria-label="Switch to <?= $isDark ? 'light' : 'dark' ?> mode"
                        title="Switch to <?= $isDark ? 'light' : 'dark' ?> mode"
                        data-csrf="<?= e($csrfToken) ?>">
                    <?php if ($isDark): ?>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                    <?php endif; ?>
                </button>

                <!-- Logout -->
                <a href="?route=logout"
                   class="icon-btn logout"
                   aria-label="Sign out of <?= e($appName) ?>"
                   title="Sign out">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                </a>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <!-- ── Flash Messages ──────────────────────────────────────────── -->
    <?php if (!empty($flashes)): ?>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 mt-2 space-y-2" role="status" aria-live="polite">
        <?php foreach ($flashes as $flash): ?>
        <div class="flash-<?= e($flash['type']) ?> rounded-lg px-4 py-3 text-sm flex items-center gap-2" role="alert">
            <?php if ($flash['type'] === 'error'): ?>
                <svg width="16" height="16" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <?php elseif ($flash['type'] === 'success'): ?>
                <svg width="16" height="16" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2" aria-hidden="true" focusable="false"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
            <?php endif; ?>
            <span><?= e($flash['message']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ── Main Content ────────────────────────────────────────────── -->
    <main id="main-content" class="flex-1" role="main">
        <?= $content ?? '' ?>
    </main>

    <!-- ── Footer ──────────────────────────────────────────────────── -->
    <?php if (is_authenticated()): ?>
    <footer class="border-t border-color-muted" role="contentinfo">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <span class="text-xs text-dimmed">
                © <?= date('Y') ?> <?= e($appName) ?> · Free &amp; Open Source
            </span>
            <div class="flex items-center gap-4 text-xs text-dimmed">
                <a href="https://github.com/boopathirbk/licenseradar" target="_blank" rel="noopener noreferrer"
                   class="flex items-center gap-1 transition-colors hover:text-zinc-300"
                   aria-label="View source code on GitHub">
                    <!-- GitHub icon -->
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                    <span>GitHub</span>
                </a>
                <a href="https://boopathirbk.github.io/licenseradar/" target="_blank" rel="noopener noreferrer"
                   class="flex items-center gap-1 transition-colors hover:text-zinc-300"
                   aria-label="View documentation">
                    <!-- Docs icon -->
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                    <span>Docs</span>
                </a>
            </div>
        </div>
    </footer>
    <?php endif; ?>

</body>
</html>
