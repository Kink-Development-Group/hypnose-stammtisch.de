<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\Validator;
use HypnoseStammtisch\Utils\RateLimiter;
use HypnoseStammtisch\Utils\AuditLogger;
use HypnoseStammtisch\Utils\EmailService;
use RuntimeException;

use function bin2hex;
use function call_user_func;
use function function_exists;
use function hash;
use function is_string;
use function openssl_random_pseudo_bytes;

/**
 * Password Reset Controller
 *
 * Handles password reset functionality for admin users via email.
 * Implements secure token-based password reset flow with rate limiting,
 * audit logging, and comprehensive security measures.
 *
 * @package HypnoseStammtisch\Controllers
 */
class PasswordResetController
{
    /**
     * Token expiration time in minutes
     */
    private const TOKEN_EXPIRATION_MINUTES = 60;

    /**
     * Maximum number of reset requests per IP per time window
     */
    private const MAX_REQUESTS_PER_IP = 3;

    /**
     * Rate limit time window in seconds (15 minutes)
     */
    private const RATE_LIMIT_WINDOW = 900;

    private const GENERIC_RESET_RESPONSE =
    'Falls ein Konto mit dieser E-Mail-Adresse existiert, wurde eine E-Mail mit Anweisungen '
        . 'zum Zurücksetzen des Passworts gesendet.';

    private const SUCCESSFUL_RESET_MESSAGE =
    'Ihr Passwort wurde erfolgreich zurückgesetzt. Sie können sich jetzt '
        . 'mit Ihrem neuen Passwort anmelden.';

    /**
     * Request password reset
     *
     * Initiates the password reset process by generating a token
     * and sending an email to the user with reset instructions.
     *
     * POST /api/admin/auth/password-reset/request
     *
     * @return void
     */
    public static function requestReset(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        // Get client IP for rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Rate limit password reset requests
        $rlKey = 'password_reset:' . $ip;
        $rl = RateLimiter::attempt($rlKey, self::MAX_REQUESTS_PER_IP, self::RATE_LIMIT_WINDOW);

        if (!$rl['allowed']) {
            AuditLogger::log('password_reset.rate_limited', null, null, ['ip' => $ip]);
            Response::error(
                'Zu viele Anfragen. Bitte versuchen Sie es später erneut.',
                429,
                ['reset' => $rl['reset']]
            );
            return;
        }

        // Parse and validate input
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = new Validator($input);
        $validator->required(['email']);
        $validator->email('email');

        if (!$validator->isValid()) {
            Response::error('Validierung fehlgeschlagen', 400, $validator->getErrors());
            return;
        }

        $email = $input['email'];

        // Find user by email
        $user = Database::fetchOne(
            'SELECT id, username, email, is_active FROM users WHERE email = ?',
            [$email]
        );

        // Always return success to prevent email enumeration attacks
        // even if the user doesn't exist
        if (!$user) {
            AuditLogger::log('password_reset.unknown_email', null, null, [
                'email' => $email,
                'ip' => $ip
            ]);

            // Still return success to prevent user enumeration
            Response::success(null, self::GENERIC_RESET_RESPONSE);
            return;
        }

        // Check if account is active
        if (!$user['is_active']) {
            AuditLogger::log('password_reset.inactive_account', 'user', (string)$user['id'], [
                'email' => $email,
                'ip' => $ip
            ]);

            // Return generic success message for security
            Response::success(null, self::GENERIC_RESET_RESPONSE);
            return;
        }

        // Invalidate any existing unused tokens for this user
        Database::execute(
            'UPDATE password_reset_tokens SET used_at = CURRENT_TIMESTAMP WHERE user_id = ? AND used_at IS NULL',
            [$user['id']]
        );

        // Generate secure token
        $token = self::generateResetToken();
        $tokenHash = hash('sha256', $token);

        // Calculate expiration time
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRATION_MINUTES . ' minutes'));

        // Store token in database
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        Database::execute(
            'INSERT INTO password_reset_tokens (user_id, token, expires_at, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?)',
            [$user['id'], $tokenHash, $expiresAt, $ip, $userAgent]
        );

        // Send password reset email
        $emailSent = EmailService::sendPasswordResetEmail(
            $user['email'],
            $user['username'],
            $token,
            self::TOKEN_EXPIRATION_MINUTES
        );

        if ($emailSent) {
            AuditLogger::log('password_reset.requested', 'user', (string)$user['id'], [
                'email' => $email,
                'ip' => $ip
            ]);
        } else {
            error_log("Failed to send password reset email to {$email}");
            AuditLogger::log('password_reset.email_failed', 'user', (string)$user['id'], [
                'email' => $email,
                'ip' => $ip
            ]);
        }

        // Always return success message (even if email fails)
        Response::success(null, self::GENERIC_RESET_RESPONSE);
    }

    /**
     * Verify password reset token
     *
     * Validates that a password reset token is valid and not expired.
     *
     * GET /api/admin/auth/password-reset/verify?token={token}
     *
     * @return void
     */
    public static function verifyToken(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }

        $token = $_GET['token'] ?? null;

        if (!$token) {
            Response::error('Token fehlt', 400);
            return;
        }

        // Validate token format
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            Response::error('Ungültiges Token-Format', 400);
            return;
        }

        $resetToken = self::findPasswordResetToken($token);

        if (!$resetToken) {
            Response::error('Token nicht gefunden oder ungültig', 404);
            return;
        }

        // Check if token was already used
        if ($resetToken['used_at'] !== null) {
            Response::error('Token wurde bereits verwendet', 400);
            return;
        }

        // Check if token is expired
        $now = new \DateTime();
        $expiresAt = new \DateTime($resetToken['expires_at']);

        if ($now > $expiresAt) {
            Response::error('Token ist abgelaufen', 400);
            return;
        }

        // Token is valid
        Response::success(['valid' => true], 'Token ist gültig');
    }

    /**
     * Reset password with token
     *
     * Completes the password reset process by validating the token
     * and updating the user's password.
     *
     * POST /api/admin/auth/password-reset/reset
     *
     * @return void
     */
    public static function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        // Get client IP for logging
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Parse and validate input
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = new Validator($input);
        $validator->required(['token', 'password']);

        if (!$validator->isValid()) {
            Response::error('Validierung fehlgeschlagen', 400, $validator->getErrors());
            return;
        }

        $token = $input['token'];
        $newPassword = $input['password'];

        // Validate password strength
        if (strlen($newPassword) < 8) {
            Response::error('Das Passwort muss mindestens 8 Zeichen lang sein', 400, [
                'password' => ['Das Passwort muss mindestens 8 Zeichen lang sein']
            ]);
            return;
        }

        // Validate token format
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            Response::error('Ungültiges Token-Format', 400);
            return;
        }

        $resetToken = self::findPasswordResetToken($token);

        if (!$resetToken) {
            AuditLogger::log('password_reset.invalid_token', null, null, ['ip' => $ip]);
            Response::error('Token nicht gefunden oder ungültig', 404);
            return;
        }

        // Check if token was already used
        if ($resetToken['used_at'] !== null) {
            AuditLogger::log('password_reset.token_reuse', 'user', (string)$resetToken['user_id'], [
                'ip' => $ip
            ]);
            Response::error('Token wurde bereits verwendet', 400);
            return;
        }

        // Check if token is expired
        $now = new \DateTime();
        $expiresAt = new \DateTime($resetToken['expires_at']);

        if ($now > $expiresAt) {
            AuditLogger::log('password_reset.token_expired', 'user', (string)$resetToken['user_id'], [
                'ip' => $ip
            ]);
            Response::error('Token ist abgelaufen', 400);
            return;
        }

        // Fetch user details
        $user = Database::fetchOne(
            'SELECT id, email, username, is_active FROM users WHERE id = ?',
            [$resetToken['user_id']]
        );

        if (!$user || !$user['is_active']) {
            AuditLogger::log('password_reset.user_not_found', 'user', (string)$resetToken['user_id'], [
                'ip' => $ip
            ]);
            Response::error('Benutzer nicht gefunden oder inaktiv', 404);
            return;
        }

        // Hash new password
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update user password
        Database::execute(
            'UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$passwordHash, $user['id']]
        );

        // Mark token as used
        Database::execute(
            'UPDATE password_reset_tokens SET used_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$resetToken['id']]
        );

        // Log successful password reset
        AuditLogger::log('password_reset.completed', 'user', (string)$user['id'], [
            'email' => $user['email'],
            'ip' => $ip
        ]);

        Response::success(['success' => true], self::SUCCESSFUL_RESET_MESSAGE);
    }

    private static function findPasswordResetToken(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);

        $resetToken = Database::fetchOne(
            'SELECT id, user_id, expires_at, used_at
             FROM password_reset_tokens
             WHERE token = ?',
            [$tokenHash]
        );

        if ($resetToken) {
            return $resetToken;
        }

        $legacyToken = Database::fetchOne(
            'SELECT id, user_id, expires_at, used_at
             FROM password_reset_tokens
             WHERE token = ?',
            [$token]
        );

        if ($legacyToken) {
            Database::execute(
                'UPDATE password_reset_tokens SET token = ? WHERE id = ?',
                [$tokenHash, $legacyToken['id']]
            );
            $legacyToken['token'] = $tokenHash;

            return $legacyToken;
        }

        return null;
    }

    private static function generateResetToken(): string
    {
        return bin2hex(self::getRandomBytes(32));
    }

    private static function getRandomBytes(int $length): string
    {
        if (function_exists('random_bytes')) {
            $bytes = call_user_func('random_bytes', $length);
            if (is_string($bytes)) {
                return $bytes;
            }
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $strong = false;
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if ($bytes !== false && $strong === true) {
                return $bytes;
            }
        }

        throw new RuntimeException('Secure random bytes generator not available');
    }

    /**
     * Clean up expired tokens (maintenance task)
     *
     * Removes expired and used tokens older than 24 hours from the database.
     * This should be called periodically via a cron job or maintenance script.
     *
     * @return int Number of deleted tokens
     */
    public static function cleanupExpiredTokens(): int
    {
        $statement = Database::execute(
            'DELETE FROM password_reset_tokens
             WHERE expires_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
             OR (used_at IS NOT NULL AND used_at < DATE_SUB(NOW(), INTERVAL 24 HOUR))'
        );

        $deleted = $statement->rowCount();

        if ($deleted > 0) {
            AuditLogger::log('password_reset.cleanup', null, null, [
                'deleted_count' => $deleted
            ]);
        }

        return $deleted;
    }
}
