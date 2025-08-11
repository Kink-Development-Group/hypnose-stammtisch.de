<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\AuditLogger;

class UserEmailConfirmController
{
  public static function confirm(string $token): void
  {
    if (!$token) {
      Response::error('Invalid token', 400);
      return;
    }
    $row = Database::fetchOne('SELECT id, pending_email FROM users WHERE email_change_token = ? AND pending_email IS NOT NULL', [$token]);
    if (!$row) {
      Response::error('Token ungültig oder abgelaufen', 400);
      return;
    }
    Database::execute('UPDATE users SET email = pending_email, pending_email = NULL, email_change_token = NULL, email_change_requested_at = NULL WHERE id = ?', [$row['id']]);
    AuditLogger::log('user.email_confirmed', 'user', (string)$row['id']);
    Response::success(['email_confirmed' => true], 'E-Mail erfolgreich bestätigt');
  }
}
