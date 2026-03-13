<?php
/**
 * LicenseRadar — Settings Page
 */

use function LicenseRadar\{e, csrf_field, current_user_id};

$theme  = $_SESSION['theme'] ?? 'dark';
$isDark = $theme === 'dark';
$userId = current_user_id();

// Load 2FA status
$emailEnabled = LicenseRadar\Database::fetchOne('SELECT enabled FROM two_factor_email WHERE user_id = ?', [$userId]);
$totpEnabled  = LicenseRadar\Database::fetchOne('SELECT enabled FROM two_factor_totp WHERE user_id = ?', [$userId]);
$passkeyCount = LicenseRadar\Database::fetchOne('SELECT COUNT(*) as cnt FROM passkeys WHERE user_id = ?', [$userId]);

$pageTitle = 'Settings';
ob_start();
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-8">

    <h1 class="text-2xl font-bold tracking-tight <?= $isDark ? 'text-white' : 'text-zinc-900' ?>">Settings</h1>

    <!-- Theme -->
    <div class="card">
        <h2 class="card-title">Appearance</h2>
        <form method="POST" action="?route=settings" class="flex items-center gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_theme">
            <div class="flex rounded-lg overflow-hidden border <?= $isDark ? 'border-zinc-800' : 'border-zinc-200' ?>">
                <button type="submit" name="theme" value="dark"
                    class="px-4 py-2 text-sm font-medium transition-colors <?= $isDark ? 'bg-zinc-800 text-white' : 'bg-transparent text-zinc-600 hover:bg-zinc-100' ?>">
                    Dark
                </button>
                <button type="submit" name="theme" value="light"
                    class="px-4 py-2 text-sm font-medium transition-colors <?= !$isDark ? 'bg-zinc-200 text-zinc-900' : 'bg-transparent text-zinc-400 hover:bg-zinc-800' ?>">
                    Light
                </button>
            </div>
        </form>
    </div>

    <!-- Password -->
    <div class="card">
        <h2 class="card-title">Change Password</h2>
        <form method="POST" action="?route=settings" class="space-y-4 max-w-sm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_password">

            <div class="space-y-1.5">
                <label for="current_password" class="block text-xs font-medium <?= $isDark ? 'text-zinc-400' : 'text-zinc-600' ?>">Current Password</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="input-field">
            </div>
            <div class="space-y-1.5">
                <label for="new_password" class="block text-xs font-medium <?= $isDark ? 'text-zinc-400' : 'text-zinc-600' ?>">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="12" autocomplete="new-password" class="input-field">
                <p class="text-xs <?= $isDark ? 'text-zinc-600' : 'text-zinc-400' ?>">Minimum 12 characters</p>
            </div>
            <div class="space-y-1.5">
                <label for="confirm_password" class="block text-xs font-medium <?= $isDark ? 'text-zinc-400' : 'text-zinc-600' ?>">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="12" autocomplete="new-password" class="input-field">
            </div>

            <button type="submit" class="btn-primary">Update Password</button>
        </form>
    </div>

    <!-- 2FA: Email OTP -->
    <div class="card">
        <h2 class="card-title">Two-Factor Authentication</h2>
        <div class="space-y-4">

            <!-- Email OTP -->
            <div class="flex items-center justify-between p-4 rounded-lg <?= $isDark ? 'bg-zinc-900/50 border border-zinc-800/50' : 'bg-zinc-50 border border-zinc-200' ?>">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg <?= $isDark ? 'bg-sky-500/10' : 'bg-sky-50' ?> flex items-center justify-center">
                        <svg class="w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium <?= $isDark ? 'text-white' : 'text-zinc-900' ?>">Email OTP</div>
                        <div class="text-xs <?= $isDark ? 'text-zinc-500' : 'text-zinc-500' ?>">6-digit code sent to your email</div>
                    </div>
                </div>
                <?php if ($emailEnabled && (int) $emailEnabled['enabled'] === 1): ?>
                    <span class="badge badge-emerald">Enabled</span>
                <?php else: ?>
                    <form method="POST" action="?route=settings">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="enable_email_2fa">
                        <button type="submit" class="btn-secondary text-xs">Enable</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- TOTP -->
            <div class="flex items-center justify-between p-4 rounded-lg <?= $isDark ? 'bg-zinc-900/50 border border-zinc-800/50' : 'bg-zinc-50 border border-zinc-200' ?>">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg <?= $isDark ? 'bg-violet-500/10' : 'bg-violet-50' ?> flex items-center justify-center">
                        <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium <?= $isDark ? 'text-white' : 'text-zinc-900' ?>">Authenticator App</div>
                        <div class="text-xs <?= $isDark ? 'text-zinc-500' : 'text-zinc-500' ?>">Google Authenticator, Authy, etc.</div>
                    </div>
                </div>
                <?php if ($totpEnabled && (int) $totpEnabled['enabled'] === 1): ?>
                    <span class="badge badge-emerald">Enabled</span>
                <?php else: ?>
                    <button type="button" class="btn-secondary text-xs" onclick="document.getElementById('totp-setup').classList.toggle('hidden')">Setup</button>
                <?php endif; ?>
            </div>

            <!-- TOTP Setup Form (hidden by default) -->
            <?php if (!$totpEnabled || (int) $totpEnabled['enabled'] !== 1): ?>
            <?php
                $totpSecret = \OTPHP\TOTP::generate()->getSecret();
                $totpObj = \OTPHP\TOTP::createFromSecret($totpSecret);
                $totpObj->setLabel($_SESSION['email'] ?? 'user');
                $totpObj->setIssuer('LicenseRadar');
            ?>
            <div id="totp-setup" class="hidden p-4 rounded-lg <?= $isDark ? 'bg-zinc-900/50 border border-zinc-800/50' : 'bg-zinc-50 border border-zinc-200' ?> space-y-3">
                <p class="text-xs <?= $isDark ? 'text-zinc-400' : 'text-zinc-600' ?>">
                    Copy this secret into your authenticator app, then enter the 6-digit code to verify:
                </p>
                <code class="block text-xs font-mono p-3 rounded-lg <?= $isDark ? 'bg-zinc-800 text-sky-400' : 'bg-zinc-100 text-sky-600' ?> break-all select-all">
                    <?= e($totpSecret) ?>
                </code>
                <form method="POST" action="?route=settings" class="flex items-end gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="enable_totp">
                    <input type="hidden" name="totp_secret" value="<?= e($totpSecret) ?>">
                    <div class="flex-1">
                        <label for="totp_code" class="block text-xs font-medium mb-1 <?= $isDark ? 'text-zinc-400' : 'text-zinc-600' ?>">Verification Code</label>
                        <input type="text" id="totp_code" name="totp_code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="input-field font-mono" placeholder="000000">
                    </div>
                    <button type="submit" class="btn-primary">Verify & Enable</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Passkey -->
            <div class="flex items-center justify-between p-4 rounded-lg <?= $isDark ? 'bg-zinc-900/50 border border-zinc-800/50' : 'bg-zinc-50 border border-zinc-200' ?>">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg <?= $isDark ? 'bg-emerald-500/10' : 'bg-emerald-50' ?> flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 10v4M8 12h8M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium <?= $isDark ? 'text-white' : 'text-zinc-900' ?>">Passkey / WebAuthn</div>
                        <div class="text-xs <?= $isDark ? 'text-zinc-500' : 'text-zinc-500' ?>">
                            <?php if ($passkeyCount && (int) $passkeyCount['cnt'] > 0): ?>
                                <?= $passkeyCount['cnt'] ?> passkey(s) registered
                            <?php else: ?>
                                Hardware key, biometric, or platform authenticator
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <span class="text-xs <?= $isDark ? 'text-zinc-600' : 'text-zinc-400' ?>">Coming soon</span>
            </div>
        </div>
    </div>

    <!-- Account Info -->
    <div class="card">
        <h2 class="card-title">Account</h2>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="<?= $isDark ? 'text-zinc-500' : 'text-zinc-500' ?>">Username</span>
                <span class="font-medium"><?= e($_SESSION['username'] ?? '') ?></span>
            </div>
            <div class="flex justify-between">
                <span class="<?= $isDark ? 'text-zinc-500' : 'text-zinc-500' ?>">Email</span>
                <span class="font-medium"><?= e($_SESSION['email'] ?? '') ?></span>
            </div>
            <div class="flex justify-between">
                <span class="<?= $isDark ? 'text-zinc-500' : 'text-zinc-500' ?>">Role</span>
                <span class="badge badge-sky"><?= e(ucfirst($_SESSION['role'] ?? 'admin')) ?></span>
            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
