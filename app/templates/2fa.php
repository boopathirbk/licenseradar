<?php
/**
 * LicenseRadar — 2FA Verification Page
 */

use function LicenseRadar\{e, csrf_field};

$theme    = $_SESSION['theme'] ?? 'dark';
$methods  = $_SESSION['2fa_methods'] ?? [];
$primary  = $methods[0] ?? 'email';
$hasPasskey = in_array('passkey', $methods, true);

$pageTitle = 'Verify Identity';
ob_start();
?>

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm space-y-8">

        <!-- Header -->
        <div class="text-center space-y-3">
            <div class="mx-auto w-12 h-12 rounded-xl card flex items-center justify-center">
                <svg class="w-6 h-6 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-heading">Verify Your Identity</h1>
                <p class="text-sm text-muted mt-1" id="2fa-description">
                    <?php if ($primary === 'email'): ?>
                        A 6-digit code was sent to your email
                    <?php elseif ($primary === 'totp'): ?>
                        Enter the code from your authenticator app
                    <?php elseif ($primary === 'passkey'): ?>
                        Use your passkey to verify
                    <?php else: ?>
                        Complete verification to continue
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Method tabs -->
        <?php if (count($methods) > 1): ?>
        <div class="flex rounded-lg card p-1" role="tablist">
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

        <!-- Code Form (for Email OTP / TOTP) -->
        <form method="POST" action="?route=2fa" class="space-y-4" id="2fa-form" <?= $primary === 'passkey' ? 'style="display:none"' : '' ?>>
            <?= csrf_field() ?>
            <input type="hidden" name="method" id="2fa-method" value="<?= e($primary) ?>">

            <div class="space-y-1.5">
                <label for="code" class="block text-xs font-medium text-label">Verification Code</label>
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

        <!-- Passkey Assertion Panel -->
        <?php if ($hasPasskey): ?>
        <div id="passkey-2fa-panel" class="space-y-4" <?= $primary !== 'passkey' ? 'style="display:none"' : '' ?>>
            <div class="text-center space-y-3">
                <div class="mx-auto w-16 h-16 rounded-xl card flex items-center justify-center">
                    <svg class="w-8 h-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
                    </svg>
                </div>
                <p class="text-xs text-muted">Click below to verify using your registered passkey</p>
            </div>
            <button type="button" id="passkey-verify-btn" class="btn-primary w-full flex items-center justify-center gap-2" aria-label="Verify with passkey">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                Verify with Passkey
            </button>
        </div>

        <script>
        (function() {
            var btn = document.getElementById('passkey-verify-btn');
            var panel = document.getElementById('passkey-2fa-panel');
            var codeForm = document.getElementById('2fa-form');
            var desc = document.getElementById('2fa-description');

            // Handle tab switching for passkey
            document.querySelectorAll('.method-tab').forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var method = this.getAttribute('data-method');
                    // Update all tabs
                    document.querySelectorAll('.method-tab').forEach(function(t) {
                        t.setAttribute('aria-selected', 'false');
                    });
                    this.setAttribute('aria-selected', 'true');

                    if (method === 'passkey') {
                        if (codeForm) codeForm.style.display = 'none';
                        if (panel) panel.style.display = '';
                        if (desc) desc.textContent = 'Use your passkey to verify';
                    } else {
                        if (codeForm) {
                            codeForm.style.display = '';
                            document.getElementById('2fa-method').value = method;
                        }
                        if (panel) panel.style.display = 'none';
                        if (desc) desc.textContent = method === 'email'
                            ? 'A 6-digit code was sent to your email'
                            : 'Enter the code from your authenticator app';
                    }
                });
            });

            if (!btn || !window.PublicKeyCredential) {
                if (btn) {
                    btn.textContent = 'Not supported in this browser';
                    btn.disabled = true;
                }
                return;
            }

            btn.addEventListener('click', async function() {
                btn.disabled = true;
                btn.textContent = 'Authenticating…';

                try {
                    var optRes = await fetch('?route=passkey/auth_options', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    var options = await optRes.json();
                    if (options.error) throw new Error(options.error);

                    function b64ToBuffer(b64) {
                        var s = b64.replace(/-/g, '+').replace(/_/g, '/');
                        while (s.length % 4) s += '=';
                        var bin = atob(s);
                        var buf = new Uint8Array(bin.length);
                        for (var i = 0; i < bin.length; i++) buf[i] = bin.charCodeAt(i);
                        return buf.buffer;
                    }
                    function bufToB64(buf) {
                        var bytes = new Uint8Array(buf);
                        var s = '';
                        bytes.forEach(function(b) { s += String.fromCharCode(b); });
                        return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
                    }

                    options.challenge = b64ToBuffer(options.challenge);
                    if (options.allowCredentials) {
                        options.allowCredentials = options.allowCredentials.map(function(c) {
                            c.id = b64ToBuffer(c.id);
                            return c;
                        });
                    }

                    var assertion = await navigator.credentials.get({ publicKey: options });

                    var verifyRes = await fetch('?route=passkey/auth_verify', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id: bufToB64(assertion.rawId),
                            type: assertion.type,
                            response: {
                                clientDataJSON: bufToB64(assertion.response.clientDataJSON),
                                authenticatorData: bufToB64(assertion.response.authenticatorData),
                                signature: bufToB64(assertion.response.signature)
                            }
                        })
                    });
                    var result = await verifyRes.json();
                    if (result.error) throw new Error(result.error);

                    window.location.href = result.redirect || '?route=dashboard';
                } catch (err) {
                    btn.disabled = false;
                    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg> Verify with Passkey';
                    if (err.name !== 'NotAllowedError') {
                        alert('Passkey verification failed: ' + err.message);
                    }
                }
            });
        })();
        </script>
        <?php endif; ?>

        <div class="text-center">
            <a href="?route=login" class="text-xs text-muted hover:text-body transition-colors">
                ← Back to login
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
