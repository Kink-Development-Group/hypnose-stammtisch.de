<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use HypnoseStammtisch\Config\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Email Service Utility
 *
 * Centralized email service for sending various types of emails
 * using PHPMailer with consistent configuration and error handling.
 *
 * @package HypnoseStammtisch\Utils
 */
class EmailService
{
  /**
   * Retrieve the application name from configuration with a sensible fallback.
   */
  private static function getAppName(): string
  {
    return Config::getAppName();
  }

  /**
   * Create and configure a PHPMailer instance
   *
   * @return PHPMailer Configured PHPMailer instance
   * @throws Exception If PHPMailer initialization fails
   */
  private static function createMailer(): PHPMailer
  {
    $mail = new PHPMailer(true);

    // Configure SMTP if enabled
    if (Config::get('mail.smtp_enabled', false)) {
      $mail->isSMTP();
      $mail->Host = Config::get('mail.smtp_host');
      $mail->SMTPAuth = true;
      $mail->Username = Config::get('mail.smtp_username');
      $mail->Password = Config::get('mail.smtp_password');
      $mail->SMTPSecure = Config::get('mail.smtp_encryption', 'tls');
      $mail->Port = Config::get('mail.smtp_port', 587);
    }

    // Set default sender
    $appName = self::getAppName();
    $mail->setFrom(
      Config::get('mail.from_email', 'noreply@hypnose-stammtisch.de'),
      Config::get('mail.from_name', $appName)
    );

    // Use HTML by default
    $mail->isHTML(true);

    return $mail;
  }

  /**
   * Send password reset email
   *
   * @param string $recipientEmail Recipient email address
   * @param string $recipientName Recipient name
   * @param string $resetToken Password reset token
   * @param int $expirationMinutes Token expiration time in minutes
   * @return bool True if email was sent successfully
   */
  public static function sendPasswordResetEmail(
    string $recipientEmail,
    string $recipientName,
    string $resetToken,
    int $expirationMinutes = 60
  ): bool {
    try {
      $mail = self::createMailer();

      // Add recipient
      $mail->addAddress($recipientEmail, $recipientName);

      // Build reset URL
      $baseUrl = Config::get('app.url', 'https://hypnose-stammtisch.de');
      $resetUrl = $baseUrl . '/admin/reset-password?token=' . urlencode($resetToken);

      // Email subject
      $appName = self::getAppName();
      $mail->Subject = 'Passwort zurücksetzen - ' . $appName . ' Admin';

      // HTML body with i18n German text
      $mail->Body = self::getPasswordResetEmailHtml($recipientName, $resetUrl, $expirationMinutes);

      // Plain text alternative
      $mail->AltBody = self::getPasswordResetEmailText($recipientName, $resetUrl, $expirationMinutes);

      // Send email
      $mail->send();

      return true;
    } catch (Exception $e) {
      error_log('Failed to send password reset email: ' . $e->getMessage());
      return false;
    } catch (\Throwable $e) {
      error_log('Unexpected error sending password reset email: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Get HTML template for password reset email
   *
   * @param string $recipientName Recipient name
   * @param string $resetUrl Password reset URL
   * @param int $expirationMinutes Token expiration time in minutes
   * @return string HTML email body
   */
  private static function getPasswordResetEmailHtml(
    string $recipientName,
    string $resetUrl,
    int $expirationMinutes
  ): string {
    $escapedName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
    $escapedUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
    $appName = htmlspecialchars(self::getAppName(), ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Passwort zurücksetzen</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
  <div style="background-color: #f4f4f4; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
    <h2 style="color: #5a67d8; margin-top: 0;">Passwort zurücksetzen</h2>
    <p>Hallo {$escapedName},</p>
    <p>Sie haben eine Anfrage zum Zurücksetzen Ihres Passworts für das {$appName} Admin-Panel gestellt.</p>
    <p>Klicken Sie auf den folgenden Link, um Ihr Passwort zurückzusetzen:</p>
    <p style="text-align: center; margin: 30px 0;">
      <a href="{$escapedUrl}"
         style="background-color: #5a67d8; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
        Passwort jetzt zurücksetzen
      </a>
    </p>
    <p>Oder kopieren Sie diesen Link in Ihren Browser:</p>
    <p style="background-color: #fff; padding: 10px; border-radius: 3px; word-break: break-all;">
      {$escapedUrl}
    </p>
    <p><strong>Wichtig:</strong> Dieser Link ist nur für {$expirationMinutes} Minuten gültig.</p>
    <p style="color: #666; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
      Falls Sie diese Anfrage nicht gestellt haben, ignorieren Sie diese E-Mail einfach.
      Ihr Passwort bleibt unverändert und sicher.
    </p>
  </div>
  <p style="color: #999; font-size: 12px; text-align: center;">
    © 2025 {$appName}. Alle Rechte vorbehalten.
  </p>
</body>
</html>
HTML;
  }

  /**
   * Get plain text template for password reset email
   *
   * @param string $recipientName Recipient name
   * @param string $resetUrl Password reset URL
   * @param int $expirationMinutes Token expiration time in minutes
   * @return string Plain text email body
   */
  private static function getPasswordResetEmailText(
    string $recipientName,
    string $resetUrl,
    int $expirationMinutes
  ): string {
    $appName = self::getAppName();

    return <<<TEXT
Passwort zurücksetzen - {$appName} Admin

Hallo {$recipientName},

Sie haben eine Anfrage zum Zurücksetzen Ihres Passworts für das {$appName} Admin-Panel gestellt.

Klicken Sie auf den folgenden Link, um Ihr Passwort zurückzusetzen:

{$resetUrl}

WICHTIG: Dieser Link ist nur für {$expirationMinutes} Minuten gültig.

Falls Sie diese Anfrage nicht gestellt haben, ignorieren Sie diese E-Mail einfach.
Ihr Passwort bleibt unverändert und sicher.

---
© 2025 {$appName}. Alle Rechte vorbehalten.
TEXT;
  }

  /**
   * Get HTML template for email change confirmation
   *
   * @param string $confirmUrl Confirmation URL
   * @param string $newEmail New email address
   * @return string HTML email body
   */
  private static function getEmailChangeConfirmationHtml(
    string $confirmUrl,
    string $newEmail
  ): string {
    $escapedUrl = htmlspecialchars($confirmUrl, ENT_QUOTES, 'UTF-8');
    $escapedEmail = htmlspecialchars($newEmail, ENT_QUOTES, 'UTF-8');
    $appName = htmlspecialchars(self::getAppName(), ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-Mail-Adresse bestätigen</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
  <div style="background-color: #f4f4f4; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
    <h2 style="color: #5a67d8; margin-top: 0;">E-Mail-Adresse bestätigen</h2>
    <p>Hallo!</p>
    <p>Du hast angefragt, deine Admin-E-Mail-Adresse auf <strong>{$escapedEmail}</strong> zu ändern.</p>
    <p>Bitte bestätige diese Änderung, indem du auf den folgenden Button klickst:</p>
    <p style="text-align: center; margin: 30px 0;">
      <a href="{$escapedUrl}"
         style="background-color: #5a67d8; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
        E-Mail jetzt bestätigen
      </a>
    </p>
    <p>Oder kopiere diesen Link in deinen Browser:</p>
    <p style="background-color: #fff; padding: 10px; border-radius: 3px; word-break: break-all;">
      {$escapedUrl}
    </p>
    <p style="color: #666; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
      Falls du diese Änderung nicht angefordert hast, ignoriere diese E-Mail bitte.
      In diesem Fall bleibt deine bisherige E-Mail-Adresse unverändert.
    </p>
  </div>
  <p style="color: #999; font-size: 12px; text-align: center;">
    © 2025 {$appName}. Alle Rechte vorbehalten.
  </p>
</body>
</html>
HTML;
  }

  /**
   * Get plain text template for email change confirmation
   *
   * @param string $confirmUrl Confirmation URL
   * @param string $newEmail New email address
   * @return string Plain text email body
   */
  private static function getEmailChangeConfirmationText(
    string $confirmUrl,
    string $newEmail
  ): string {
    $appName = self::getAppName();

    return <<<TEXT
E-Mail-Adresse bestätigen - {$appName}

Du hast angefragt, deine Admin-E-Mail-Adresse auf {$newEmail} zu ändern.

Bitte bestätige die Änderung über den folgenden Link:

{$confirmUrl}

Falls du diese Änderung nicht angefordert hast, ignoriere diese E-Mail einfach.
In diesem Fall bleibt deine bisherige E-Mail-Adresse unverändert.

---
© 2025 {$appName}. Alle Rechte vorbehalten.
TEXT;
  }

  /**
   * Send email change confirmation
   *
   * @param string|null $oldEmail Old email address (optional, for CC)
   * @param string $newEmail New email address
   * @param string $confirmationToken Email change confirmation token
   * @return bool True if email was sent successfully
   */
  public static function sendEmailChangeConfirmation(
    ?string $oldEmail,
    string $newEmail,
    string $confirmationToken
  ): bool {
    try {
      $mail = self::createMailer();

      // Add recipient
      $mail->addAddress($newEmail);

      // Add CC to old email if provided
      if ($oldEmail && $oldEmail !== $newEmail) {
        $mail->addCC($oldEmail);
      }

      // Build confirmation URL
      $baseUrl = Config::get('app.url', 'https://hypnose-stammtisch.de');
      $confirmUrl = $baseUrl . '/api/admin/users/confirm-email?token=' . urlencode($confirmationToken);

      // Email subject
      $appName = self::getAppName();
      $mail->Subject = 'E-Mail Änderung bestätigen - ' . $appName;

      // HTML body
      $mail->Body = self::getEmailChangeConfirmationHtml($confirmUrl, $newEmail);

      // Plain text alternative
      $mail->AltBody = self::getEmailChangeConfirmationText($confirmUrl, $newEmail);

      // Send email
      $mail->send();

      return true;
    } catch (Exception $e) {
      error_log('Failed to send email change confirmation: ' . $e->getMessage());
      return false;
    } catch (\Throwable $e) {
      error_log('Unexpected error sending email change confirmation: ' . $e->getMessage());
      return false;
    }
  }
}
