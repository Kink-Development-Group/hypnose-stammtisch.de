<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Middleware\AdminAuth;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\AuditLogger;
use HypnoseStammtisch\Utils\Validator;
use HypnoseStammtisch\Utils\EmailService;
use HypnoseStammtisch\Config\Config;

class UserController
{
    public static function me(): void
    {
        AdminAuth::requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', 405);
            return;
        }
        $user = AdminAuth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
            return;
        }
        Response::success($user);
    }

    public static function updateMe(): void
    {
        AdminAuth::requireAuth();
        if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'PATCH'], true)) {
            Response::error('Method not allowed', 405);
            return;
        }
        AdminAuth::requireCSRF();
        $current = AdminAuth::getCurrentUser();
        if (!$current) {
            Response::unauthorized();
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $validator = new Validator($input);
        if (isset($input['email'])) {
            $validator->email('email');
        }
        $errors = [];
        if (isset($input['password']) && strlen((string)$input['password']) < 8) {
            $errors['password'][] = 'Password too short (min 8)';
        }
        if (isset($input['username']) && strlen((string)$input['username']) < 3) {
            $errors['username'][] = 'Username too short (min 3)';
        }
        if (isset($input['role'])) {
            Response::error('Role cannot be changed', 403);
            return;
        }
        if (!$validator->isValid() || $errors) {
            Response::error('Validation failed', 400, array_merge($validator->getErrors(), $errors));
            return;
        }
        if (!empty($input['reset_twofa'])) {
            $currentPassword = (string)($input['current_password'] ?? '');
            if ($currentPassword === '') {
                Response::error('Current password required to reset 2FA', 400, [
                    'current_password' => ['Current password is required to reset 2FA']
                ]);
                return;
            }

            $account = Database::fetchOne('SELECT password_hash FROM users WHERE id = ?', [$current['id']]);
            if (!$account || empty($account['password_hash']) || !password_verify($currentPassword, $account['password_hash'])) {
                Response::error('Current password is incorrect', 403, [
                    'current_password' => ['Current password is incorrect']
                ]);
                return;
            }
        }
        $fields = [];
        $params = [];
        if (isset($input['username']) && $input['username'] !== $current['username']) {
            $fields[] = 'username = ?';
            $params[] = $input['username'];
        }
        if (isset($input['email']) && $input['email'] !== $current['email']) {
            // create pending email change with token
            $token = self::generateSecureToken();
            $fields[] = 'pending_email = ?';
            $params[] = $input['email'];
            $fields[] = 'email_change_token = ?';
            $params[] = $token;
            $fields[] = 'email_change_requested_at = CURRENT_TIMESTAMP';
            // send confirmation email
            self::sendEmailChangeConfirmation($current['email'], $input['email'], $token);
        }
        if (!empty($input['password'])) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        if (!empty($input['reset_twofa'])) {
            $fields[] = 'twofa_secret = NULL';
            $fields[] = 'twofa_enabled = 0';
        }
        if (!$fields) {
            Response::success(['updated' => false, 'user' => $current], 'No changes');
            return;
        }
        $params[] = $current['id'];
        Database::execute('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
        if (!empty($input['reset_twofa'])) {
            Database::execute('DELETE FROM user_twofa_backup_codes WHERE user_id = ?', [$current['id']]);
        }
        AuditLogger::log('user.profile_update', 'user', (string)$current['id'], ['fields' => $fields]);
        $updated = AdminAuth::getCurrentUser();
        Response::success(['updated' => true, 'user' => $updated], 'Profile updated');
    }

    public static function adminUpdateUser(string $id): void
    {
        AdminAuth::requireAuth();
        $actor = AdminAuth::getCurrentUser();
        if (!$actor || $actor['role'] !== 'head') {
            Response::unauthorized(['message' => 'Head admin required']);
            return;
        }
        if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'PATCH'], true)) {
            Response::error('Method not allowed', 405);
            return;
        }
        AdminAuth::requireCSRF();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $validator = new Validator($input);
        if (isset($input['email'])) {
            $validator->email('email');
        }
        $errors = [];
        if (isset($input['password']) && strlen((string)$input['password']) < 8) {
            $errors['password'][] = 'Password too short (min 8)';
        }
        if (isset($input['username']) && strlen((string)$input['username']) < 3) {
            $errors['username'][] = 'Username too short (min 3)';
        }
        if (isset($input['role']) && !in_array($input['role'], ['admin', 'moderator', 'head', 'event_manager'], true)) {
            Response::error('Invalid role', 400);
            return;
        }
        if ((string)$actor['id'] === $id && isset($input['role']) && $input['role'] !== 'head') {
            Response::error('Cannot downgrade own role', 400);
            return;
        }
        if ((string)$actor['id'] === $id && !empty($input['reset_twofa'])) {
            Response::error('Use your profile settings to reset your own 2FA', 400);
            return;
        }
        if (!$validator->isValid() || $errors) {
            Response::error('Validation failed', 400, array_merge($validator->getErrors(), $errors));
            return;
        }
        $target = Database::fetchOne('SELECT id FROM users WHERE id = ?', [$id]);
        if (!$target) {
            Response::error('User not found', 404);
            return;
        }
        $fields = [];
        $params = [];
        foreach (['username', 'email'] as $f) {
            if (isset($input[$f])) {
                if ($f === 'email') {
                    $token = self::generateSecureToken();
                    $fields[] = 'pending_email = ?';
                    $params[] = $input[$f];
                    $fields[] = 'email_change_token = ?';
                    $params[] = $token;
                    $fields[] = 'email_change_requested_at = CURRENT_TIMESTAMP';
                    // send confirmation to new email only
                    self::sendEmailChangeConfirmation(null, $input[$f], $token);
                } else {
                    $fields[] = "$f = ?";
                    $params[] = $input[$f];
                }
            }
        }
        if (!empty($input['password'])) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        if (isset($input['role'])) {
            $fields[] = 'role = ?';
            $params[] = $input['role'];
        }
        if (isset($input['is_active'])) {
            $fields[] = 'is_active = ?';
            $params[] = $input['is_active'] ? 1 : 0;
        }
        if (!empty($input['reset_twofa'])) {
            $fields[] = 'twofa_secret = NULL';
            $fields[] = 'twofa_enabled = 0';
        }
        if (!$fields) {
            Response::success(['updated' => false], 'No changes');
            return;
        }
        $params[] = $id;
        Database::execute('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
        if (!empty($input['reset_twofa'])) {
            Database::execute('DELETE FROM user_twofa_backup_codes WHERE user_id = ?', [$id]);
        }
        AuditLogger::log('admin.user_update', 'user', (string)$id, ['fields' => $fields]);
        $user = Database::fetchOne('SELECT id, username, email, role, is_active, last_login, created_at, updated_at FROM users WHERE id = ?', [$id]);
        Response::success(['updated' => true, 'user' => $user], 'User updated');
    }

    private static function sendEmailChangeConfirmation(?string $oldEmail, string $newEmail, string $token): void
    {
        EmailService::sendEmailChangeConfirmation($oldEmail, $newEmail, $token);
    }

    private static function generateSecureToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}
