<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Middleware;

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\Response;

/**
 * Admin authentication middleware
 */
class AdminAuth
{
  /** Roles definitions for permission checks. */
    public const HEAD_ADMIN_ROLES = ['head'];
    public const SECURITY_MANAGEMENT_ROLES = ['head', 'admin'];
    public const MESSAGE_MANAGEMENT_ROLES = ['head', 'admin', 'moderator'];
    public const EVENT_MANAGEMENT_ROLES = ['head', 'admin', 'event_manager'];
    public const EVENT_MANAGER_ONLY_ROLES = ['event_manager'];

  /**
   * Start secure session if not already started
   */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $sessionLifetime = (int)Config::get('session.lifetime', 3600 * 8);

            $sessionName = Config::get('session.name', 'hypnose_session');
            if (session_name() !== $sessionName) {
                session_name($sessionName);
            }

            $cookieParams = [
            'lifetime' => $sessionLifetime,
            'path' => '/',
            'secure' => self::shouldUseSecureCookies(),
            'httponly' => true,
            'samesite' => 'Lax'
            ];

            $cookieDomain = self::determineCookieDomain();
            if ($cookieDomain !== null) {
                $cookieParams['domain'] = $cookieDomain;
            }

          // Set session cookie parameters before starting session
            session_set_cookie_params($cookieParams);

            session_start([
            'use_strict_mode' => true,
            'cookie_lifetime' => $sessionLifetime,
            'gc_maxlifetime' => $sessionLifetime,
            'gc_probability' => 1,
            'gc_divisor' => 100,
            ]);

          // Regenerate session ID periodically for security
            if (!isset($_SESSION['session_started'])) {
                  session_regenerate_id(true);
                  $_SESSION['session_started'] = time();
            } elseif (time() - $_SESSION['session_started'] > 1800) { // 30 minutes
                session_regenerate_id(true);
                $_SESSION['session_started'] = time();
            }
        }
    }

  /**
   * Check if user is authenticated as admin (incl. 2FA)
   */
    public static function isAuthenticated(): bool
    {
        self::startSession();
        return isset($_SESSION['admin_user_id']) && isset($_SESSION['admin_user_email']) && !empty($_SESSION['admin_2fa_verified']);
    }

  /**
   * Get current authenticated admin user
   */
    public static function getCurrentUser(): ?array
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        $sql = "SELECT id, username, email, role, is_active, last_login, created_at, updated_at
            FROM users WHERE id = ? AND is_active = 1";
        $user = Database::fetchOne($sql, [$_SESSION['admin_user_id']]);

        if ($user) {
          // Ensure is_active is boolean and convert datetime fields to ISO format
            $user['is_active'] = (bool)$user['is_active'];
            $user['last_login'] = $user['last_login'] ? date('c', strtotime($user['last_login'])) : null;
            $user['created_at'] = date('c', strtotime($user['created_at']));
            $user['updated_at'] = date('c', strtotime($user['updated_at']));
        }

        return $user ?: null;
    }

  /**
   * Determine if a user belongs to one of the allowed roles.
   */
    public static function userHasRole(?array $user, array $allowedRoles): bool
    {
        if (!$user || !isset($user['role'])) {
            return false;
        }

        return in_array($user['role'], $allowedRoles, true);
    }

  /**
   * Authenticate user with email & password (stage 1 of 2FA)
   */
    public static function authenticate(string $email, string $password, string $ipAddress = ''): array
    {
        $sql = "SELECT id, username, email, password_hash, role, is_active, locked_until, locked_reason, twofa_secret, twofa_enabled, created_at, updated_at
            FROM users WHERE email = ? AND is_active = 1";
        $user = Database::fetchOne($sql, [$email]);

      // Get IP if not provided
        if (empty($ipAddress)) {
            $ipAddress = \HypnoseStammtisch\Utils\IpBanManager::getClientIP();
        }

      // Check if user exists and password is correct
        if (!$user || !password_verify($password, $user['password_hash'])) {
          // Handle failed login - even if user doesn't exist, we track by IP and username
            \HypnoseStammtisch\Utils\FailedLoginTracker::handleFailedLogin(
                $user ? (int)$user['id'] : null,
                $email,
                $ipAddress
            );

            return ['success' => false, 'message' => 'Invalid credentials'];
        }

      // Check if account is locked
        if (\HypnoseStammtisch\Utils\FailedLoginTracker::isAccountLocked((int)$user['id'])) {
            return [
            'success' => false,
            'message' => 'Account is temporarily locked due to security reasons'
            ];
        }

      // Success - clear any failed attempts for this account
        \HypnoseStammtisch\Utils\FailedLoginTracker::clearFailedAttemptsForAccount((int)$user['id']);

      // Start session
        self::startSession();
        session_regenerate_id(true);

        $_SESSION['admin_user_password_ok'] = true;
        $_SESSION['admin_2fa_verified'] = false;
        $_SESSION['admin_user_pending_id'] = $user['id'];

        $twofaConfigured = !empty($user['twofa_secret']) && (bool)$user['twofa_enabled'];

        return [
        'success' => true,
        'user_id' => $user['id'],
        'twofa_required' => true,
        'twofa_configured' => $twofaConfigured,
        'message' => $twofaConfigured ? 'Second factor required' : '2FA setup required'
        ];
    }

  /**
   * Logout current user
   */
    public static function logout(): void
    {
        self::startSession();

      // Clear all session data
        session_unset();

      // Destroy the session
        session_destroy();

      // Clear the session cookie by setting it to expire in the past
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', self::cookieOptions(time() - 3600));
        }

      // Also clear any additional admin-specific cookies if they exist
        $adminCookies = ['admin_session', 'admin_token', 'admin_remember'];
        foreach ($adminCookies as $cookieName) {
            if (isset($_COOKIE[$cookieName])) {
                setcookie($cookieName, '', self::cookieOptions(time() - 3600));
            }
        }
    }

  /**
   * Require admin authentication
   */
    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            Response::unauthorized(['message' => 'Authentication required']);
            exit;
        }
    }

  /**
   * Finalize 2FA after successful verification
   */
  /**
   * Finalize 2FA nach erfolgreicher Verifikation.
   * Akzeptiert sowohl int als auch string, da Session IDs numerisch gespeichert sein können.
   */
    public static function finalizeTwoFactor(int|string $userId): array
    {
      // Ensure string for DB layer (Prepared Statements akzeptieren beides, wir normalisieren dennoch)
        $idParam = (string)$userId;
        $sql = "SELECT id, username, email, role, is_active, last_login, created_at, updated_at, twofa_enabled FROM users WHERE id = ?";
        $user = Database::fetchOne($sql, [$idParam]);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        Database::execute("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?", [$idParam]);

        $user['last_login'] = date('c');

        $_SESSION['admin_user_id'] = $user['id']; // bleibt numerisch oder string wie aus DB
        $_SESSION['admin_user_email'] = $user['email'];
        $_SESSION['admin_user_role'] = $user['role'];
        $_SESSION['admin_2fa_verified'] = true;

        unset($_SESSION['admin_user_pending_id'], $_SESSION['admin_user_password_ok']);

        return [
        'success' => true,
        'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'is_active' => (bool)$user['is_active'],
        'last_login' => $user['last_login'],
        'created_at' => date('c', strtotime($user['created_at'])),
        'updated_at' => date('c', strtotime($user['updated_at']))
        ]
        ];
    }

  /**
   * Verify CSRF token
   * Supports tokens from POST, GET, JSON body (X-CSRF-Token), and custom header
   */
    public static function verifyCSRF(string $token = null): bool
    {
        self::startSession();
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

      // Try to get token from various sources
      // Check both $_SERVER and getallheaders() for the CSRF token
        $headerToken = null;
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
          // Headers are case-insensitive, so check common variations
            $headerToken = $headers['X-CSRF-Token']
            ?? $headers['X-Csrf-Token']
            ?? $headers['x-csrf-token']
            ?? $headers['X-CSRF-TOKEN']
            ?? null;
        }

        $providedToken = $token
        ?? $_POST['csrf_token']
        ?? $_GET['csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $headerToken
        ?? null;

      // Also check JSON body if not found (last resort)
        if ($providedToken === null) {
            $input = file_get_contents('php://input');
            if ($input) {
                $json = json_decode($input, true);
                $providedToken = $json['csrf_token'] ?? null;
            }
        }

      // Debug logging for CSRF issues (can be removed after debugging)
        if ($providedToken === null) {
            error_log('CSRF Debug - No token found. Session token exists: ' . (isset($_SESSION['csrf_token']) ? 'yes' : 'no'));
            error_log('CSRF Debug - HTTP_X_CSRF_TOKEN: ' . ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? 'not set'));
            if (function_exists('getallheaders')) {
                $allHeaders = getallheaders();
                $csrfHeaders = array_filter($allHeaders, fn($k) => stripos($k, 'csrf') !== false, ARRAY_FILTER_USE_KEY);
                error_log('CSRF Debug - Headers with csrf: ' . json_encode($csrfHeaders));
            }
        }

        return $providedToken && hash_equals($_SESSION['csrf_token'], $providedToken);
    }

  /**
   * Require valid CSRF token for mutating requests (POST, PUT, DELETE, PATCH)
   * Stops execution and returns 403 if invalid
   */
    public static function requireCSRF(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

      // Only check CSRF for mutating methods
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            return;
        }

        if (!self::verifyCSRF()) {
            Response::error('Invalid or missing CSRF token', 403);
            exit;
        }
    }

  /**
   * Generate CSRF token
   */
    public static function generateCSRF(): string
    {
        self::startSession();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(self::secureRandomBytes(32));
        }
        return $_SESSION['csrf_token'];
    }

  /** Determine the cookie domain for session handling */
    private static function determineCookieDomain(): ?string
    {
        $domain = $_ENV['SESSION_DOMAIN'] ?? null;

        if (!$domain) {
            $appUrl = Config::get('app.url', '');
            if (is_string($appUrl) && $appUrl !== '') {
                $domain = parse_url($appUrl, PHP_URL_HOST) ?: null;
            }

            if (!$domain && isset($_SERVER['HTTP_HOST'])) {
                $domain = $_SERVER['HTTP_HOST'];
            }
        }

        if (!$domain) {
            return null;
        }

        $domain = strtolower(preg_replace('/:\d+$/', '', trim((string)$domain)) ?? '');
        $domain = preg_replace('/^www\./', '', $domain);

        if ($domain === '' || $domain === 'localhost' || $domain === '127.0.0.1') {
            return null;
        }

        return $domain;
    }

  /** Decide whether session cookies must be marked as secure */
    private static function shouldUseSecureCookies(): bool
    {
        if (isset($_ENV['SESSION_SECURE'])) {
            return filter_var($_ENV['SESSION_SECURE'], FILTER_VALIDATE_BOOLEAN);
        }

        $host = $_SERVER['HTTP_HOST'] ?? (parse_url(Config::get('app.url', ''), PHP_URL_HOST) ?: '');
        if ($host === 'localhost' || $host === '127.0.0.1') {
            return false;
        }

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        $scheme = parse_url(Config::get('app.url', ''), PHP_URL_SCHEME);
        return $scheme === 'https';
    }

  /** Build consistent cookie options for setcookie operations */
    private static function cookieOptions(?int $expires = null): array
    {
        $options = [
        'path' => '/',
        'secure' => self::shouldUseSecureCookies(),
        'httponly' => true,
        'samesite' => 'Lax',
        ];

        $domain = self::determineCookieDomain();
        if ($domain !== null) {
            $options['domain'] = $domain;
        }

        if ($expires !== null) {
            $options['expires'] = $expires;
        }

        return $options;
    }

  /** Provide a secure source of random bytes with graceful fallback */
    private static function secureRandomBytes(int $length): string
    {
        if (function_exists('random_bytes')) {
            return (string) \call_user_func('random_bytes', $length);
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $strong = false;
            $bytes = \openssl_random_pseudo_bytes($length, $strong);
            if ($bytes !== false && $strong) {
                return $bytes;
            }
        }

        throw new \RuntimeException('No suitable CSPRNG available for session token generation');
    }
}
