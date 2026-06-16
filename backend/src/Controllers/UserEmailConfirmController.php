<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\AuditLogger;

class UserEmailConfirmController
{
    /** Confirmation links stay valid for 24 hours after the change was requested. */
    private const TOKEN_TTL_SECONDS = 86400;

    /**
     * Confirm a pending email change.
     *
     * This endpoint is opened directly by the user's browser from the link in
     * the confirmation mail, so it renders a self-contained HTML page instead
     * of a JSON API response (the previous behaviour showed raw JSON, which
     * looked like the link was broken).
     */
    public static function confirm(string $token): void
    {
        if ($token === '') {
            self::renderPage(400, false, 'Ungültiger Link', 'Der Bestätigungslink ist unvollständig.');
            return;
        }

        $row = Database::fetchOne(
            'SELECT id, pending_email, email_change_requested_at FROM users WHERE email_change_token = ? AND pending_email IS NOT NULL',
            [$token]
        );

        if (!$row) {
            self::renderPage(400, false, 'Link ungültig', 'Dieser Bestätigungslink ist ungültig oder wurde bereits verwendet.');
            return;
        }

        // Reject (and clean up) links whose request is older than the TTL.
        $requestedAt = $row['email_change_requested_at'] ?? null;
        if ($requestedAt !== null) {
            $requestedTs = strtotime((string)$requestedAt);
            if ($requestedTs !== false && (time() - $requestedTs) > self::TOKEN_TTL_SECONDS) {
                Database::execute(
                    'UPDATE users SET pending_email = NULL, email_change_token = NULL, email_change_requested_at = NULL WHERE id = ?',
                    [$row['id']]
                );
                AuditLogger::log('user.email_change_expired', 'user', (string)$row['id']);
                self::renderPage(400, false, 'Link abgelaufen', 'Dieser Bestätigungslink ist abgelaufen. Bitte fordere die E-Mail-Änderung erneut an.');
                return;
            }
        }

        try {
            Database::execute(
                'UPDATE users SET email = pending_email, pending_email = NULL, email_change_token = NULL, email_change_requested_at = NULL WHERE id = ?',
                [$row['id']]
            );
        } catch (\Throwable $e) {
            // Most likely a UNIQUE violation because the address was taken in
            // the meantime. Keep the pending change so the user can retry and
            // still show a friendly page instead of a raw 500.
            error_log('Email change confirmation failed: ' . $e->getMessage());
            self::renderPage(409, false, 'Bestätigung fehlgeschlagen', 'Die E-Mail-Adresse konnte nicht bestätigt werden. Möglicherweise wird sie bereits von einem anderen Konto verwendet.');
            return;
        }

        AuditLogger::log('user.email_confirmed', 'user', (string)$row['id']);

        self::renderPage(200, true, 'E-Mail bestätigt', 'Deine neue E-Mail-Adresse wurde erfolgreich bestätigt. Du kannst dich jetzt mit ihr anmelden.');
    }

    /**
     * Render a minimal, self-contained confirmation result page.
     */
    private static function renderPage(int $status, bool $success, string $heading, string $message): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
        }

        $appName = htmlspecialchars(Config::getAppName(), ENT_QUOTES, 'UTF-8');
        $heading = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $loginUrl = htmlspecialchars(
            rtrim((string)Config::get('app.url', ''), '/') . '/#/admin/login',
            ENT_QUOTES,
            'UTF-8'
        );
        $accent = $success ? '#16a34a' : '#dc2626';
        $icon = $success ? '✓' : '✕';

        echo <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$heading} – {$appName}</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; color:#333; margin:0; padding:40px 20px;">
  <div style="max-width:520px; margin:0 auto; background:#fff; border-radius:8px; padding:32px; box-shadow:0 1px 4px rgba(0,0,0,.08); text-align:center;">
    <div style="width:56px; height:56px; line-height:56px; margin:0 auto 16px; border-radius:50%; background:{$accent}; color:#fff; font-size:28px;">{$icon}</div>
    <h1 style="margin:0 0 12px; font-size:22px; color:{$accent};">{$heading}</h1>
    <p style="margin:0 0 24px; line-height:1.6;">{$message}</p>
    <a href="{$loginUrl}" style="display:inline-block; background:#5a67d8; color:#fff; padding:12px 28px; border-radius:6px; text-decoration:none;">Zum Admin-Login</a>
  </div>
  <p style="text-align:center; color:#999; font-size:12px; margin-top:24px;">© {$appName}</p>
</body>
</html>
HTML;
    }
}
