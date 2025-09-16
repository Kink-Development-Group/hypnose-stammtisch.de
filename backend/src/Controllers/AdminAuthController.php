<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Middleware\AdminAuth;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\RateLimiter;
use HypnoseStammtisch\Utils\AuditLogger;
use HypnoseStammtisch\Utils\Validator;

/**
 * Admin authentication controller
 */
class AdminAuthController
{
  /**
   * Handle login request
   */
  public static function login(): void
  {
    AdminAuth::startSession();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Response::error('Method not allowed', 405);
      return;
    }

    // Check for IP ban first - before any other processing
    $ip = \HypnoseStammtisch\Utils\IpBanManager::getClientIP();
    $ipBanCheck = \HypnoseStammtisch\Utils\IpBanManager::checkIPBanMiddleware($ip);
    if ($ipBanCheck['blocked']) {
      Response::error('Access denied', 403);
      return;
    }

    $rlKey = 'login:' . $ip;
    $rl = RateLimiter::attempt($rlKey, 10, 300); // 10 Versuche pro 5 Minuten
    if (!$rl['allowed']) {
      Response::error('Too many login attempts. Try again later.', 429, ['reset' => $rl['reset']]);
      return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $validator = new Validator($input);
    $validator->required(['email', 'password']);
    $validator->email('email');

    if (!$validator->isValid()) {
      Response::error('Validation failed', 400, $validator->getErrors());
      return;
    }

    $result = AdminAuth::authenticate($input['email'], $input['password'], $ip);

    if ($result['success']) {
      AuditLogger::log('auth.login_stage1', 'user', (string)$result['user_id']);
      Response::success([
        'twofa_required' => $result['twofa_required'],
        'twofa_configured' => $result['twofa_configured']
      ], $result['message']);
    } else {
      AuditLogger::log('auth.login_failed', null, null, ['email' => $input['email'] ?? null, 'ip' => $ip]);
      Response::error($result['message'], 401);
    }
  }

  /**
   * Handle logout request
   */
  public static function logout(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Response::error('Method not allowed', 405);
      return;
    }

    $user = AdminAuth::getCurrentUser();
    if ($user) {
      AuditLogger::log('auth.logout', 'user', (string)$user['id']);
    }

    // Perform logout
    AdminAuth::logout();

    // Add additional headers to prevent caching
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    Response::success(null, 'Logout successful');
  }

  /**
   * Get current authentication status
   */
  public static function status(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    // Add cache prevention headers
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $user = AdminAuth::getCurrentUser();

    if (!$user && isset($_SESSION['admin_user_pending_id'])) {
      Response::success([
        'twofa_pending' => true,
        'twofa_configured' => true
      ], '2FA verification pending');
      return;
    }

    if ($user) {
      // Log successful status check
      error_log("Admin status check: User {$user['username']} is authenticated");
      Response::success($user, 'Authenticated');
    } else {
      // Log failed status check
      error_log("Admin status check: No authenticated user found");
      Response::unauthorized(['message' => 'Not authenticated']);
    }
  }

  /**
   * Get CSRF token
   */
  public static function csrf(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    $token = AdminAuth::generateCSRF();
    Response::success(['csrf_token' => $token]);
  }

  /**
   * 2FA setup (generate secret)
   */
  public static function twofaSetup(): void
  {
    AdminAuth::startSession();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Response::error('Method not allowed', 405);
      return;
    }

    if (empty($_SESSION['admin_user_pending_id'])) {
      Response::error('No pending login', 400);
      return;
    }

    $userId = $_SESSION['admin_user_pending_id'];
    $user = Database::fetchOne('SELECT id, email, twofa_secret, twofa_enabled FROM users WHERE id = ?', [$userId]);

    if (!$user) {
      Response::error('User not found', 404);
      return;
    }

    if (!empty($user['twofa_secret']) && $user['twofa_enabled']) {
      Response::success(['message' => '2FA already configured']);
      return;
    }

    if (empty($user['twofa_secret'])) {
      $secret = \HypnoseStammtisch\Utils\Totp::generateSecret();
      Database::execute('UPDATE users SET twofa_secret = ? WHERE id = ?', [$secret, $userId]);
      $user['twofa_secret'] = $secret;
    } else {
      $secret = $user['twofa_secret'];
    }

    $issuer = 'HypnoseStammtisch';
    $uri = \HypnoseStammtisch\Utils\Totp::provisioningUri($secret, $user['email'], $issuer);
    Response::success([
      'secret' => $secret,
      'otpauth_uri' => $uri
    ], '2FA secret generated');
  }

  /**
   * 2FA verify code
   */
  public static function twofaVerify(): void
  {
    AdminAuth::startSession();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Response::error('Method not allowed', 405);
      return;
    }

    if (empty($_SESSION['admin_user_pending_id'])) {
      Response::error('No pending login', 400);
      return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rlKey = '2fa:' . ($ip) . ':' . ($_SESSION['admin_user_pending_id'] ?? 'none');
    $rl = RateLimiter::attempt($rlKey, 15, 600); // 15 Codes pro 10 Minuten
    if (!$rl['allowed']) {
      Response::error('Too many 2FA attempts. Try again later.', 429, ['reset' => $rl['reset']]);
      return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $validator = new Validator($input);
    $validator->required(['code']);

    if (!$validator->isValid()) {
      Response::error('Validation failed', 400, $validator->getErrors());
      return;
    }

    $userId = $_SESSION['admin_user_pending_id'];

    // rate limit attempts (in session)
    $_SESSION['twofa_attempts'] = ($_SESSION['twofa_attempts'] ?? 0) + 1;
    if ($_SESSION['twofa_attempts'] > 10) { // hard cap
      Response::error('Too many attempts. Start over.', 429);
      return;
    }

    $user = Database::fetchOne('SELECT id, email, twofa_secret, twofa_enabled FROM users WHERE id = ?', [$userId]);

    if (!$user || empty($user['twofa_secret'])) {
      Response::error('2FA not initialized', 400);
      return;
    }

    $code = (string)$input['code'];
    $valid = \HypnoseStammtisch\Utils\Totp::verify($user['twofa_secret'], $code);

    if (!$valid) {
      // try backup code
      $backup = Database::fetchOne('SELECT id, code_hash FROM user_twofa_backup_codes WHERE user_id = ? AND used_at IS NULL', [$userId]);
      if ($backup && password_verify($code, $backup['code_hash'])) {
        Database::execute('UPDATE user_twofa_backup_codes SET used_at = CURRENT_TIMESTAMP WHERE id = ?', [$backup['id']]);
        $valid = true;
      }
    }

    if (!$valid) {
      AuditLogger::log('auth.2fa_failed');
      Response::error('Invalid 2FA code', 401);
      return;
    }

    $wasDisabled = !(bool)$user['twofa_enabled'];
    if ($wasDisabled) {
      Database::execute('UPDATE users SET twofa_enabled = 1 WHERE id = ?', [$userId]);
    }

    $final = AdminAuth::finalizeTwoFactor($userId); // sets authenticated session

    // If first-time activation, pre-generate backup codes if none exist
    $backupCodes = null;
    if ($final['success'] && $wasDisabled) {
      $existing = Database::fetchOne('SELECT COUNT(*) as c FROM user_twofa_backup_codes WHERE user_id = ?', [$userId]);
      if (!$existing || (int)$existing['c'] === 0) {
        $backupCodes = [];
        for ($i = 0; $i < 10; $i++) {
          $codePlain = strtoupper(bin2hex(random_bytes(4)));
          Database::execute('INSERT INTO user_twofa_backup_codes (user_id, code_hash) VALUES (?, ?)', [$userId, password_hash($codePlain, PASSWORD_DEFAULT)]);
          $backupCodes[] = $codePlain;
        }
      }
    }
    unset($_SESSION['twofa_attempts']);

    if ($final['success']) {
      AuditLogger::log('auth.login_success', 'user', (string)$userId, ['first_activation' => $wasDisabled]);
      $payload = $final['user'];
      if ($backupCodes !== null) {
        $payload = array_merge($payload, ['backup_codes' => $backupCodes]);
      }
      Response::success($payload, 'Login successful');
    } else {
      Response::error($final['message'] ?? 'Unknown error', 500);
    }
  }

  /** Generate backup codes (must be after full auth) */
  public static function twofaBackupGenerate(): void
  {
    if (!AdminAuth::isAuthenticated()) {
      Response::unauthorized();
      return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Response::error('Method not allowed', 405);
      return;
    }

    $user = AdminAuth::getCurrentUser();
    if (!$user) {
      Response::unauthorized();
      return;
    }

    AuditLogger::log('2fa.backup_regenerate');
    // delete existing
    Database::execute('DELETE FROM user_twofa_backup_codes WHERE user_id = ?', [$user['id']]);
    $plainCodes = [];
    for ($i = 0; $i < 10; $i++) {
      $codePlain = strtoupper(bin2hex(random_bytes(4))); // 8 hex chars
      $plainCodes[] = $codePlain;
      Database::execute('INSERT INTO user_twofa_backup_codes (user_id, code_hash) VALUES (?, ?)', [$user['id'], password_hash($codePlain, PASSWORD_DEFAULT)]);
    }
    Response::success(['codes' => $plainCodes], 'Backup codes generated');
  }

  /** List remaining backup codes count */
  public static function twofaBackupStatus(): void
  {
    if (!AdminAuth::isAuthenticated()) {
      Response::unauthorized();
      return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    $user = AdminAuth::getCurrentUser();
    $remaining = Database::fetchOne('SELECT COUNT(*) as c FROM user_twofa_backup_codes WHERE user_id = ? AND used_at IS NULL', [$user['id']]);
    Response::success(['remaining' => (int)$remaining['c']], 'Backup code status');
  }
}
