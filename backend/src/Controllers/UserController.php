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
        if (!empty($input['password'])) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        if (!empty($input['reset_twofa'])) {
            $fields[] = 'twofa_secret = NULL';
            $fields[] = 'twofa_enabled = 0';
        }
        // The pending-email columns are written in the SAME update as the other
        // fields and the confirmation mail is sent AFTER that update — so the
        // token is guaranteed to exist in the DB by the time the link reaches
        // the user (no dead-link race if the write were to fail). $fields stays
        // the non-email fields so we can tell a pure email change apart and log
        // only what actually persisted.
        $emailChangeRequested = isset($input['email']) && $input['email'] !== $current['email'];
        $allFields = $fields;
        $allParams = $params;
        $token = null;
        if ($emailChangeRequested) {
            $token = self::generateSecureToken();
            $allFields[] = 'pending_email = ?';
            $allParams[] = $input['email'];
            $allFields[] = 'email_change_token = ?';
            $allParams[] = $token;
            $allFields[] = 'email_change_requested_at = CURRENT_TIMESTAMP';
        }
        if (!$allFields) {
            Response::success(['updated' => false, 'user' => $current], 'No changes');
            return;
        }
        $allParams[] = $current['id'];
        Database::execute('UPDATE users SET ' . implode(', ', $allFields) . ' WHERE id = ?', $allParams);

        $emailChangeFailed = false;
        if ($emailChangeRequested && !self::sendEmailChangeConfirmation($current['email'], $input['email'], $token)) {
            // Roll back only the pending-email columns so we never keep a change
            // the user could never confirm (#123); the other fields stay saved.
            Database::execute(
                'UPDATE users SET pending_email = NULL, email_change_token = NULL, email_change_requested_at = NULL WHERE id = ?',
                [$current['id']]
            );
            $emailChangeFailed = true;
        }
        // Email was the ONLY requested change and its mail failed → nothing was
        // persisted (the pending columns were rolled back). Report an honest 502.
        if ($emailChangeFailed && !$fields) {
            Response::error('Die Bestätigungs-E-Mail konnte nicht gesendet werden. Bitte versuche es später erneut.', 502);
            return;
        }
        if (!empty($input['reset_twofa'])) {
            Database::execute('DELETE FROM user_twofa_backup_codes WHERE user_id = ?', [$current['id']]);
        }
        // Log only the fields that actually persisted (exclude rolled-back columns).
        AuditLogger::log('user.profile_update', 'user', (string)$current['id'], ['fields' => $emailChangeFailed ? $fields : $allFields]);
        $updated = AdminAuth::getCurrentUser();
        if ($emailChangeFailed) {
            // The other fields were persisted; only the e-mail confirmation
            // failed. Report success — so the client keeps and reflects the
            // saved changes — but flag the failed e-mail so the UI warns
            // instead of silently claiming the address change is pending (#123).
            Response::success(
                ['updated' => true, 'user' => $updated, 'email_change_failed' => true],
                'Deine Änderungen wurden gespeichert, aber die Bestätigungs-E-Mail für die neue Adresse konnte nicht gesendet werden. Die E-Mail-Adresse bleibt unverändert. Bitte versuche es später erneut.'
            );
            return;
        }
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
        $target = Database::fetchOne('SELECT id, username, email, role, is_active FROM users WHERE id = ?', [$id]);
        if (!$target) {
            Response::error('User not found', 404);
            return;
        }
        $fields = [];
        $params = [];
        // The admin form always resubmits every (pre-filled) field, so compare
        // against the stored values and only write the ones that actually
        // changed — otherwise every save triggers no-op UPDATEs and a
        // misleading audit-log entry implying fields changed that did not.
        if (isset($input['username']) && $input['username'] !== $target['username']) {
            $fields[] = 'username = ?';
            $params[] = $input['username'];
        }
        if (!empty($input['password'])) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        if (isset($input['role']) && $input['role'] !== $target['role']) {
            $fields[] = 'role = ?';
            $params[] = $input['role'];
        }
        if (isset($input['is_active']) && ($input['is_active'] ? 1 : 0) !== (int)$target['is_active']) {
            $fields[] = 'is_active = ?';
            $params[] = $input['is_active'] ? 1 : 0;
        }
        if (!empty($input['reset_twofa'])) {
            $fields[] = 'twofa_secret = NULL';
            $fields[] = 'twofa_enabled = 0';
        }
        // The pending-email columns go into the same UPDATE as the other fields
        // and the confirmation mail is sent afterwards (see updateMe() — avoids a
        // dead-link race). Pass the target's current address as the old email so
        // the account holder is notified (CC'd) about the admin-initiated change
        // (#128); the fourth arg flags the mail as admin-initiated so its wording
        // does not falsely claim the holder requested it. Only act when the
        // address actually changes — the admin form always resubmits the
        // pre-filled email, so without this guard every save would trigger a
        // spurious confirmation flow.
        $emailChangeRequested = isset($input['email']) && $input['email'] !== $target['email'];
        $allFields = $fields;
        $allParams = $params;
        $token = null;
        if ($emailChangeRequested) {
            $token = self::generateSecureToken();
            $allFields[] = 'pending_email = ?';
            $allParams[] = $input['email'];
            $allFields[] = 'email_change_token = ?';
            $allParams[] = $token;
            $allFields[] = 'email_change_requested_at = CURRENT_TIMESTAMP';
        }
        if (!$allFields) {
            Response::success(['updated' => false], 'No changes');
            return;
        }
        $allParams[] = $id;
        Database::execute('UPDATE users SET ' . implode(', ', $allFields) . ' WHERE id = ?', $allParams);

        $emailChangeFailed = false;
        if ($emailChangeRequested && !self::sendEmailChangeConfirmation($target['email'], $input['email'], $token, true)) {
            // Roll back only the pending-email columns; keep the other field changes.
            Database::execute(
                'UPDATE users SET pending_email = NULL, email_change_token = NULL, email_change_requested_at = NULL WHERE id = ?',
                [$id]
            );
            $emailChangeFailed = true;
        }
        if ($emailChangeFailed && !$fields) {
            Response::error('Die Bestätigungs-E-Mail konnte nicht gesendet werden. Bitte versuche es später erneut.', 502);
            return;
        }
        if (!empty($input['reset_twofa'])) {
            Database::execute('DELETE FROM user_twofa_backup_codes WHERE user_id = ?', [$id]);
        }
        AuditLogger::log('admin.user_update', 'user', (string)$id, ['fields' => $emailChangeFailed ? $fields : $allFields]);
        // Include pending_email so the admin UI can surface an in-flight email
        // change instead of appearing to have done nothing (#125).
        $user = Database::fetchOne('SELECT id, username, email, pending_email, role, is_active, last_login, created_at, updated_at FROM users WHERE id = ?', [$id]);
        if ($emailChangeFailed) {
            // Other fields were persisted; only the e-mail confirmation failed.
            // Report success with a flag so the admin UI refreshes the saved
            // changes and warns about the e-mail instead of treating the whole
            // request as failed (#123).
            Response::success(
                ['updated' => true, 'user' => $user, 'email_change_failed' => true],
                'Die Änderungen wurden gespeichert, aber die Bestätigungs-E-Mail für die neue Adresse konnte nicht gesendet werden. Die E-Mail-Adresse bleibt unverändert. Bitte versuche es später erneut.'
            );
            return;
        }
        Response::success(['updated' => true, 'user' => $user], 'User updated');
    }

    private static function sendEmailChangeConfirmation(?string $oldEmail, string $newEmail, string $token, bool $adminInitiated = false): bool
    {
        return EmailService::sendEmailChangeConfirmation($oldEmail, $newEmail, $token, $adminInitiated);
    }

    private static function generateSecureToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}
