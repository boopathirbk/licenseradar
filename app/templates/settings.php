<?php
/**
 * LicenseRadar — Settings Page
 */

use function LicenseRadar\{e, csrf_field};

$theme  = $_SESSION['theme'] ?? 'dark';
$isDark = $theme === 'dark';
$userId = LicenseRadar\current_user_id();

// Load 2FA status (gracefully handle if tables don't exist yet)
try {
    $emailEnabled = LicenseRadar\Database::fetchOne('SELECT enabled FROM two_factor_email WHERE user_id = ?', [$userId]);
    $totpEnabled  = LicenseRadar\Database::fetchOne('SELECT enabled, secret FROM two_factor_totp WHERE user_id = ?', [$userId]);
    $passkeyCount = LicenseRadar\Database::fetchOne('SELECT COUNT(*) as cnt FROM passkeys WHERE user_id = ?', [$userId]);
} catch (\Throwable $ex) {
    $emailEnabled = null;
    $totpEnabled  = null;
    $passkeyCount = null;
}

$isEmailOn = $emailEnabled && (int) $emailEnabled['enabled'] === 1;
$isTotpOn  = $totpEnabled && (int) $totpEnabled['enabled'] === 1;

$pageTitle = 'Settings';
ob_start();
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">

    <h1 class="text-2xl font-bold tracking-tight text-heading">Settings</h1>

    <!-- ── Appearance ──────────────────────────────────────────────── -->
    <section class="card" aria-labelledby="appearance-title">
        <h2 id="appearance-title" class="card-title">Appearance</h2>
        <p class="text-xs text-muted mb-3">Choose your preferred colour scheme</p>
        <div class="theme-switch-group" id="settings-theme-group" data-csrf="<?= e(\LicenseRadar\csrf_token()) ?>">
            <button type="button" data-set-theme="dark"
                class="theme-btn <?= $isDark ? 'active' : '' ?>" aria-label="Switch to dark mode"
                aria-pressed="<?= $isDark ? 'true' : 'false' ?>">
                Dark
            </button>
            <button type="button" data-set-theme="light"
                class="theme-btn <?= !$isDark ? 'active' : '' ?>" aria-label="Switch to light mode"
                aria-pressed="<?= !$isDark ? 'true' : 'false' ?>">
                Light
            </button>
        </div>
    </section>

    <!-- ── Change Password ─────────────────────────────────────────── -->
    <section class="card" aria-labelledby="password-title">
        <h2 id="password-title" class="card-title">Change Password</h2>
        <p class="text-xs text-muted mb-3">Update your account password. Minimum 12 characters.</p>
        <form method="POST" action="?route=settings" class="space-y-3 max-w-sm" aria-label="Change password form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_password">

            <div class="space-y-1">
                <label for="current_password" class="block text-xs font-medium text-label">Current Password</label>
                <div class="input-password-wrap">
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="input-field" aria-required="true">
                    <button type="button" class="eye-toggle" aria-label="Show password" data-target="current_password">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="space-y-1">
                <label for="new_password" class="block text-xs font-medium text-label">New Password</label>
                <div class="input-password-wrap">
                    <input type="password" id="new_password" name="new_password" required minlength="12" autocomplete="new-password" class="input-field" aria-required="true">
                    <button type="button" class="eye-toggle" aria-label="Show password" data-target="new_password">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="space-y-1">
                <label for="confirm_password" class="block text-xs font-medium text-label">Confirm New Password</label>
                <div class="input-password-wrap">
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="12" autocomplete="new-password" class="input-field" aria-required="true">
                    <button type="button" class="eye-toggle" aria-label="Show password" data-target="confirm_password">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary text-sm" aria-label="Save new password">Update Password</button>
        </form>
    </section>

    <!-- ── Two-Factor Authentication ───────────────────────────────── -->
    <section class="card" aria-labelledby="twofa-title">
        <h2 id="twofa-title" class="card-title">Two-Factor Authentication</h2>
        <p class="text-xs text-muted mb-4">Add an extra layer of security to your account</p>

        <div class="space-y-3">

            <!-- Email OTP -->
            <div class="twofa-row">
                <div class="flex items-center gap-3">
                    <div class="twofa-icon twofa-icon-sky">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0ea5e9" stroke-width="2" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-body">Email OTP</div>
                        <div class="text-xs text-muted">6-digit code sent to your email on sign-in</div>
                    </div>
                </div>
                <?php if ($isEmailOn): ?>
                    <div class="flex items-center gap-2">
                        <span class="badge badge-emerald">Enabled</span>
                        <form method="POST" action="?route=settings" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="disable_email_2fa">
                            <button type="submit" class="btn-sm-secondary" aria-label="Disable email OTP">Disable</button>
                        </form>
                    </div>
                <?php else: ?>
                    <form method="POST" action="?route=settings" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="enable_email_2fa">
                        <button type="submit" class="btn-sm-secondary" aria-label="Enable email OTP">Enable</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- TOTP / Authenticator App -->
            <div class="twofa-row-expandable">
                <div class="twofa-row-header">
                    <div class="flex items-center gap-3">
                        <div class="twofa-icon twofa-icon-violet">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-body">Authenticator App</div>
                            <div class="text-xs text-muted">Google Authenticator, Authy, Microsoft Authenticator</div>
                        </div>
                    </div>
                    <?php if ($isTotpOn): ?>
                        <div class="flex items-center gap-2">
                            <span class="badge badge-emerald">Enabled</span>
                            <form method="POST" action="?route=settings" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="disable_totp">
                                <button type="submit" class="btn-sm-secondary" aria-label="Disable authenticator app">Disable</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <button type="button" class="btn-sm-secondary" onclick="document.getElementById('totp-setup').classList.toggle('hidden')" aria-label="Set up authenticator app" aria-expanded="false" aria-controls="totp-setup">Setup</button>
                    <?php endif; ?>
                </div>

                <!-- TOTP Setup (hidden by default) -->
                <?php if (!$isTotpOn): ?>
                <?php
                    $totpSecret = \OTPHP\TOTP::generate()->getSecret();
                    $totpObj = \OTPHP\TOTP::createFromSecret($totpSecret);
                    $totpObj->setLabel($_SESSION['email'] ?? 'user');
                    $totpObj->setIssuer('LicenseRadar');
                    $totpUri = $totpObj->getProvisioningUri();
                ?>
                <div id="totp-setup" class="hidden totp-setup-panel">

                    <p class="text-xs text-muted">
                        Scan the QR code below with your authenticator app, or manually enter the secret key.
                    </p>

                    <!-- QR Code -->
                    <div class="flex flex-col items-center gap-3">
                        <div id="totp-qr" class="qr-container" aria-label="QR code for authenticator app setup"></div>
                        <p class="text-xs text-muted">Scan with your authenticator app</p>
                    </div>

                    <!-- Manual Secret -->
                    <div>
                        <label class="block text-xs font-medium text-label mb-1">Manual Entry Key</label>
                        <code class="totp-secret-display">
                            <?= e($totpSecret) ?>
                        </code>
                    </div>

                    <!-- Verify Form -->
                    <form method="POST" action="?route=settings" class="flex items-end gap-2" aria-label="Verify authenticator code">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="enable_totp">
                        <input type="hidden" name="totp_secret" value="<?= e($totpSecret) ?>">
                        <div class="flex-1">
                            <label for="totp_code" class="block text-xs font-medium text-label mb-1">6-Digit Code</label>
                            <input type="text" id="totp_code" name="totp_code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                                   class="input-field font-mono" placeholder="000000" aria-required="true" aria-describedby="totp-hint">
                            <span id="totp-hint" class="sr-only">Enter the 6-digit code shown in your authenticator app</span>
                        </div>
                        <button type="submit" class="btn-primary text-xs" style="padding:8px 14px;font-size:0.75rem" aria-label="Verify code and enable authenticator">Verify & Enable</button>
                    </form>

                    <!-- QR Code Script -->
                    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js" integrity="sha384-lQXOAyZwHXE55JFyrOMB7nY2Wv+m5ZWNtJcHrd1rceRQXAYNLak8ukN5TjBTcIwz" crossorigin="anonymous"></script>
                    <script>
                    (function() {
                        var qr = qrcode(0, 'M');
                        qr.addData(<?= json_encode($totpUri) ?>);
                        qr.make();
                        document.getElementById('totp-qr').innerHTML = qr.createSvgTag(4, 0);
                    })();
                    </script>
                </div>
                <?php endif; ?>
            </div>

            <!-- Passkey / WebAuthn -->
            <div class="twofa-row">
                <div class="flex items-center gap-3">
                    <div class="twofa-icon twofa-icon-emerald">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-body">Passkey / WebAuthn</div>
                        <div class="text-xs text-muted">
                            <?php if ($passkeyCount && (int) $passkeyCount['cnt'] > 0): ?>
                                <?= $passkeyCount['cnt'] ?> passkey(s) registered
                            <?php else: ?>
                                Hardware key, biometric, or platform authenticator
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <span class="text-xs text-dimmed">Coming soon</span>
            </div>
        </div>
    </section>

    <!-- ── Account Info ────────────────────────────────────────────── -->
    <section class="card" aria-labelledby="account-title">
        <h2 id="account-title" class="card-title">Account</h2>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-muted">Username</dt>
                <dd class="font-medium text-body"><?= e($_SESSION['username'] ?? '') ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-muted">Email</dt>
                <dd class="font-medium text-body"><?= e($_SESSION['email'] ?? '') ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-muted">Role</dt>
                <dd><span class="badge badge-sky"><?= e(ucfirst($_SESSION['role'] ?? 'admin')) ?></span></dd>
            </div>
        </dl>
    </section>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
