<?php
/**
 * LicenseRadar — 2FA Verification Page
 */

use function LicenseRadar\{e, csrf_field};

$theme    = $_SESSION['theme'] ?? 'dark';
$isDark   = $theme === 'dark';
$methods  = $_SESSION['2fa_methods'] ?? [];
$primary  = $methods[0] ?? 'email';

$pageTitle = 'Verify Identity';
ob_start();
?>

<div class="min-h-screen flex items-center justify-center px-4 <?= $isDark ? 'bg-zinc-950' : 'bg-zinc-50' ?>">
    <div class="w-full max-w-sm space-y-8">

        <!-- Header -->
        <div class="text-center space-y-3">
            <div class="mx-auto w-12 h-12 rounded-xl <?= $isDark ? 'bg-zinc-900 border border-zinc-800' : 'bg-white border border-zinc-200 shadow-sm' ?> flex items-center justify-center">
                <svg class="w-6 h-6 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight <?= $isDark ? 'text-white' : 'text-zinc-900' ?>">Verify Your Identity</h1>
                <p class="text-sm <?= $isDark ? 'text-zinc-500' : 'text-zinc-500' ?> mt-1">
                    <?php if ($primary === 'email'): ?>
                        A 6-digit code was sent to your email
                    <?php elseif ($primary === 'totp'): ?>
                        Enter the code from your authenticator app
                    <?php else: ?>
                        Complete verification to continue
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Method tabs -->
        <?php if (count($methods) > 1): ?>
        <div class="flex rounded-lg <?= $isDark ? 'bg-zinc-900 border border-zinc-800' : 'bg-zinc-100 border border-zinc-200' ?> p-1" role="tablist">
            <?php foreach ($methods as $m): ?>
            <button type="button"
                class="flex-1 text-xs font-medium py-1.5 rounded-md transition-colors method-tab"
                data-method="<?= e($m) ?>"
                role="tab"
                aria-selected="<?= $m === $primary ? 'true' : 'false' ?>"
            >
                <?= ucfirst($m === 'email' ? 'Email OTP' : ($m === 'totp' ? 'Authenticator' : 'Passkey')) ?>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="?route=2fa" class="space-y-4" id="2fa-form">
            <?= csrf_field() ?>
            <input type="hidden" name="method" id="2fa-method" value="<?= e($primary) ?>">

            <div class="space-y-1.5">
                <label for="code" class="block text-xs font-medium <?= $isDark ? 'text-zinc-400' : 'text-zinc-600' ?>">Verification Code</label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    required
                    autofocus
                    autocomplete="one-time-code"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    class="input-field text-center text-lg tracking-[0.3em] font-mono"
                    placeholder="000000"
                >
            </div>

            <button type="submit" class="btn-primary w-full">
                Verify
            </button>
        </form>

        <div class="text-center">
            <a href="?route=login" class="text-xs <?= $isDark ? 'text-zinc-500 hover:text-zinc-300' : 'text-zinc-500 hover:text-zinc-700' ?> transition-colors">
                ← Back to login
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
