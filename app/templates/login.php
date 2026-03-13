<?php
/**
 * LicenseRadar — Login Page
 */

use function LicenseRadar\{e, csrf_field};

$theme  = $_SESSION['theme'] ?? 'dark';
$isDark = $theme === 'dark';

$pageTitle = 'Login';
ob_start();
?>

<div class="min-h-screen flex items-center justify-center px-4 <?= $isDark ? 'bg-zinc-950' : 'bg-zinc-50' ?>">
    <div class="w-full max-w-sm space-y-8">

        <!-- Logo -->
        <div class="text-center space-y-3">
            <div class="mx-auto w-12 h-12 rounded-xl <?= $isDark ? 'bg-zinc-900 border border-zinc-800' : 'bg-white border border-zinc-200 shadow-sm' ?> flex items-center justify-center">
                <svg width="24" height="24" viewBox="0 0 32 32" fill="none">
                    <circle cx="16" cy="16" r="14" stroke="url(#gl)" stroke-width="2.5" fill="none"/>
                    <circle cx="16" cy="16" r="8" stroke="url(#gl)" stroke-width="2" fill="none" opacity=".5"/>
                    <circle cx="16" cy="16" r="3" fill="#38bdf8"/>
                    <defs><linearGradient id="gl" x1="4" y1="4" x2="28" y2="28"><stop stop-color="#38bdf8"/><stop offset="1" stop-color="#a78bfa"/></linearGradient></defs>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight <?= $isDark ? 'text-white' : 'text-zinc-900' ?>">Sign in to LicenseRadar</h1>
                <p class="text-sm <?= $isDark ? 'text-zinc-500' : 'text-zinc-500' ?> mt-1">Enter your credentials to continue</p>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="?route=login" class="space-y-4">
            <?= csrf_field() ?>

            <div class="space-y-1.5">
                <label for="username" class="block text-xs font-medium <?= $isDark ? 'text-zinc-400' : 'text-zinc-600' ?>">Username or Email</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    autofocus
                    autocomplete="username"
                    class="input-field"
                    placeholder="admin"
                >
            </div>

            <div class="space-y-1.5">
                <label for="password" class="block text-xs font-medium <?= $isDark ? 'text-zinc-400' : 'text-zinc-600' ?>">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="input-field"
                    placeholder="••••••••••••"
                >
            </div>

            <button type="submit" class="btn-primary w-full">
                Sign In
            </button>
        </form>

        <p class="text-center text-xs <?= $isDark ? 'text-zinc-600' : 'text-zinc-400' ?>">
            Free & open-source · <a href="https://github.com/boopathirbk/licenseradar" target="_blank" rel="noopener noreferrer" class="underline">View source</a>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
