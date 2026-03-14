<?php
/**
 * LicenseRadar — Authentication
 * Login, session management, rate limiting, and 2FA verification.
 */

declare(strict_types=1);

namespace LicenseRadar;

use PHPMailer\PHPMailer\PHPMailer;
use OTPHP\TOTP;

final class Auth
{
    /**
     * Attempt login with username/password.
     *
     * @return array{success: bool, error: string, requires_2fa: bool, user_id: int|null, 2fa_methods: array<string>}
     */
    public function attemptLogin(string $username, string $password): array
    {
        $fail = ['success' => false, 'error' => '', 'requires_2fa' => false, 'user_id' => null, '2fa_methods' => []];

        // Rate limiting
        if ($this->isRateLimited()) {
            $fail['error'] = 'Too many login attempts. Please wait 15 minutes.';
            return $fail;
        }

        if ($username === '' || $password === '') {
            $fail['error'] = 'Username and password are required.';
            return $fail;
        }

        $user = Database::fetchOne(
            'SELECT id, password_hash FROM users WHERE username = ? OR email = ?',
            [$username, $username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            audit_log('login_failed', "Failed login for: {$username}");
            $fail['error'] = 'Invalid username or password.';
            return $fail;
        }

        // Check 2FA methods
        $methods = $this->get2FAMethods((int) $user['id']);

        if (!empty($methods)) {
            // Send email OTP if email 2FA is enabled
            if (in_array('email', $methods, true)) {
                $this->sendEmailOTP((int) $user['id']);
            }

            return [
                'success'      => true,
                'error'        => '',
                'requires_2fa' => true,
                'user_id'      => (int) $user['id'],
                '2fa_methods'  => $methods,
            ];
        }

        return [
            'success'      => true,
            'error'        => '',
            'requires_2fa' => false,
            'user_id'      => (int) $user['id'],
            '2fa_methods'  => [],
        ];
    }

    /**
     * Complete the login — set session variables.
     */
    public function completeLogin(int $userId): void
    {
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $user = Database::fetchOne('SELECT id, username, email, role, theme FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            return;
        }

        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['email']         = $user['email'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['theme']         = $user['theme'];
        $_SESSION['authenticated'] = true;
        $_SESSION['_created_at']   = time();
        $_SESSION['_last_activity'] = time();

        // Store session in DB
        $sessionId = session_id();
        Database::query(
            'INSERT INTO sessions (id, user_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)',
            [
                $sessionId,
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? '',
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512),
                date('Y-m-d H:i:s', time() + Config::getInt('SESSION_LIFETIME', 1800)),
            ]
        );

        // Clean up old attempts
        Database::query(
            'DELETE FROM login_attempts WHERE ip_address = ?',
            [$_SERVER['REMOTE_ADDR'] ?? '']
        );

        audit_log('login_success', "User logged in: {$user['username']}");
    }

    /**
     * Get enabled 2FA methods for a user.
     *
     * @return array<string>
     */
    public function get2FAMethods(int $userId): array
    {
        $methods = [];

        $email = Database::fetchOne('SELECT enabled FROM two_factor_email WHERE user_id = ?', [$userId]);
        if ($email && (int) $email['enabled'] === 1) {
            $methods[] = 'email';
        }

        $totp = Database::fetchOne('SELECT enabled FROM two_factor_totp WHERE user_id = ?', [$userId]);
        if ($totp && (int) $totp['enabled'] === 1) {
            $methods[] = 'totp';
        }

        $passkey = Database::fetchOne('SELECT id FROM passkeys WHERE user_id = ? LIMIT 1', [$userId]);
        if ($passkey) {
            $methods[] = 'passkey';
        }

        return $methods;
    }

    /**
     * Verify a 2FA code.
     */
    public function verify2FA(int $userId, string $method, string $code): bool
    {
        return match ($method) {
            'email'  => $this->verifyEmailOTP($userId, $code),
            'totp'   => $this->verifyTOTP($userId, $code),
            default  => false,
        };
    }

    // ── Email OTP ────────────────────────────────────────────────────

    private function sendEmailOTP(int $userId): void
    {
        $user = Database::fetchOne('SELECT email FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            return;
        }

        $code    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', time() + 300); // 5 minutes

        // Store OTP
        $existing = Database::fetchOne('SELECT id FROM two_factor_email WHERE user_id = ?', [$userId]);
        if ($existing) {
            Database::query(
                'UPDATE two_factor_email SET code = ?, expires = ? WHERE user_id = ?',
                [$code, $expires, $userId]
            );
        }

        // Send email
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = Config::get('SMTP_HOST');
            $mail->SMTPAuth   = true;
            $mail->Username   = Config::get('SMTP_USER');
            $mail->Password   = Config::get('SMTP_PASS');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = Config::getInt('SMTP_PORT', 587);

            $mail->setFrom(Config::get('SMTP_FROM_EMAIL'), Config::get('SMTP_FROM_NAME', 'LicenseRadar'));
            $mail->addAddress($user['email']);

            $mail->isHTML(true);
            $mail->Subject = 'LicenseRadar — Verification Code';
            $mail->Body    = $this->otpEmailTemplate($code);
            $mail->AltBody = "Your LicenseRadar verification code is: {$code}. It expires in 5 minutes.";

            $mail->send();
        } catch (\Throwable $e) {
            audit_log('otp_send_failed', $e->getMessage());
        }
    }

    private function verifyEmailOTP(int $userId, string $code): bool
    {
        // Atomic: clear the OTP and check if the row was updated.
        // This prevents TOCTOU race — two concurrent requests can't both succeed
        // because the first UPDATE clears the code, so the second finds no match.
        $stmt = Database::query(
            'UPDATE two_factor_email SET code = NULL, expires = NULL WHERE user_id = ? AND enabled = 1 AND code = ? AND expires > NOW()',
            [$userId, $code]
        );

        return $stmt->rowCount() > 0;
    }

    private function otpEmailTemplate(string $code): string
    {
        return <<<HTML
        <div style="font-family: 'Segoe UI', system-ui, sans-serif; max-width: 400px; margin: 0 auto; padding: 32px;">
            <h2 style="color: #18181b; margin-bottom: 8px;">Verification Code</h2>
            <p style="color: #71717a; font-size: 14px;">Enter this code in LicenseRadar to complete your login:</p>
            <div style="background: #f4f4f5; border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0;">
                <span style="font-size: 32px; font-weight: 700; letter-spacing: 8px; color: #18181b;">{$code}</span>
            </div>
            <p style="color: #a1a1aa; font-size: 12px;">This code expires in 5 minutes. If you didn't request this, ignore this email.</p>
        </div>
        HTML;
    }

    // ── TOTP ─────────────────────────────────────────────────────────

    private function verifyTOTP(int $userId, string $code): bool
    {
        $record = Database::fetchOne(
            'SELECT secret, last_used_code FROM two_factor_totp WHERE user_id = ? AND enabled = 1',
            [$userId]
        );

        if (!$record) {
            return false;
        }

        $otp = TOTP::createFromSecret($record['secret']);
        if (!$otp->verify($code, null, 1)) {
            return false;
        }

        // Prevent TOTP replay — reject if same code was already used
        $lastUsed = $record['last_used_code'] ?? '';
        if ($lastUsed !== '' && hash_equals($lastUsed, $code)) {
            return false;
        }

        // Store used code to prevent replay within the same time window
        Database::query(
            'UPDATE two_factor_totp SET last_used_code = ? WHERE user_id = ? AND enabled = 1',
            [$code, $userId]
        );

        return true;
    }

    // ── Rate Limiting ────────────────────────────────────────────────

    private function isRateLimited(): bool
    {
        $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
        $window = Config::getInt('RATE_LIMIT_WINDOW', 900);
        $max    = Config::getInt('RATE_LIMIT_ATTEMPTS', 5);

        // Record attempt FIRST, then check — prevents race condition where
        // concurrent requests all pass the check before any are recorded.
        Database::query(
            'INSERT INTO login_attempts (ip_address) VALUES (?)',
            [$ip]
        );

        $since = date('Y-m-d H:i:s', time() - $window);
        $row = Database::fetchOne(
            'SELECT COUNT(*) as cnt FROM login_attempts WHERE ip_address = ? AND attempted_at > ?',
            [$ip, $since]
        );

        return $row && (int) $row['cnt'] > $max; // > instead of >= because current attempt is already counted
    }
}
