<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\Validator;
use HypnoseStammtisch\Utils\RRuleProcessor;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Config\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Carbon\Carbon;

/**
 * Form submission controller
 * Handles event submissions and contact forms with validation and spam protection
 */
class FormController
{
  /**
   * Submit new event for review
   * POST /api/forms/submit-event
   */
  public function submitEvent(): void
  {
    try {
      // Rate limiting check
      if (!$this->checkRateLimit('submit-event', 3, 3600)) { // 3 per hour
        Response::json([
          'success' => false,
          'error' => 'Too many submissions. Please wait before submitting again.'
        ], 429);
        return;
      }

      // Get and validate input
      $input = json_decode(file_get_contents('php://input'), true);
      if (json_last_error() !== JSON_ERROR_NONE) {
        Response::json([
          'success' => false,
          'error' => 'Invalid JSON in request body'
        ], 400);
        return;
      }

      // Honeypot and timestamp spam protection
      if (!$this->validateSpamProtection($input)) {
        Response::json([
          'success' => false,
          'error' => 'Spam protection failed'
        ], 400);
        return;
      }

      // Validate event data
      $validation = $this->validateEventSubmission($input);
      if (!$validation['valid']) {
        Response::json([
          'success' => false,
          'errors' => $validation['errors']
        ], 400);
        return;
      }

      // Sanitize and prepare data
      $eventData = $this->sanitizeEventData($input);

      // Store submission for moderation
      $submissionId = $this->storeEventSubmission($eventData);

      if ($submissionId) {
        // Send confirmation email
        $this->sendEventSubmissionConfirmation($eventData);

        // Notify admins
        $this->notifyAdminsOfSubmission($submissionId, $eventData);

        Response::json([
          'success' => true,
          'message' => 'Event submission received! We will review it and get back to you.',
          'submission_id' => $submissionId
        ]);
      } else {
        throw new \Exception('Failed to store event submission');
      }
    } catch (\Exception $e) {
      error_log("Event submission error: " . $e->getMessage());
      Response::json([
        'success' => false,
        'error' => 'Failed to submit event. Please try again later.'
      ], 500);
    }
  }

  /**
   * Submit contact form
   * POST /api/forms/contact
   */
  public function submitContact(): void
  {
    try {
      // Rate limiting check
      if (!$this->checkRateLimit('contact', 5, 3600)) { // 5 per hour
        Response::json([
          'success' => false,
          'error' => 'Too many messages. Please wait before sending another message.'
        ], 429);
        return;
      }

      // Get and validate input
      $input = json_decode(file_get_contents('php://input'), true);
      if (json_last_error() !== JSON_ERROR_NONE) {
        Response::json([
          'success' => false,
          'error' => 'Invalid JSON in request body'
        ], 400);
        return;
      }

      // Honeypot and timestamp spam protection
      if (!$this->validateSpamProtection($input)) {
        Response::json([
          'success' => false,
          'error' => 'Spam protection failed'
        ], 400);
        return;
      }

      // Validate contact data
      $validation = $this->validateContactSubmission($input);
      if (!$validation['valid']) {
        Response::json([
          'success' => false,
          'errors' => $validation['errors']
        ], 400);
        return;
      }

      // Sanitize and prepare data
      $contactData = $this->sanitizeContactData($input);

      // Store contact submission
      $submissionId = $this->storeContactSubmission($contactData);

      if ($submissionId) {
        // Send confirmation email
        $this->sendContactConfirmation($contactData);

        // Notify admins
        $this->notifyAdminsOfContact($submissionId, $contactData);

        Response::json([
          'success' => true,
          'message' => 'Message sent successfully! We will get back to you soon.',
          'submission_id' => $submissionId
        ]);
      } else {
        throw new \Exception('Failed to store contact submission');
      }
    } catch (\Exception $e) {
      error_log("Contact submission error: " . $e->getMessage());
      Response::json([
        'success' => false,
        'error' => 'Failed to send message. Please try again later.'
      ], 500);
    }
  }

  /**
   * Validate event submission data
   */
  private function validateEventSubmission(array $data): array
  {
    $errors = [];

    // Required fields
    $required = [
      'title' => 'Event title is required',
      'description' => 'Event description is required',
      'start_datetime' => 'Start date and time is required',
      'end_datetime' => 'End date and time is required',
      'location_type' => 'Location type is required',
      'organizer_name' => 'Organizer name is required',
      'organizer_email' => 'Organizer email is required',
      'consent_data_processing' => 'Data processing consent is required',
      'accepted_code_of_conduct' => 'Code of conduct acceptance is required'
    ];

    foreach ($required as $field => $message) {
      if (empty($data[$field])) {
        $errors[] = $message;
      }
    }

    // Email validation
    if (!empty($data['organizer_email']) && !filter_var($data['organizer_email'], FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Invalid email address';
    }

    // Date validation
    if (!empty($data['start_datetime']) && !empty($data['end_datetime'])) {
      try {
        $start = Carbon::parse($data['start_datetime']);
        $end = Carbon::parse($data['end_datetime']);

        if ($end->lte($start)) {
          $errors[] = 'End time must be after start time';
        }

        if ($start->lt(Carbon::now())) {
          $errors[] = 'Event cannot be scheduled in the past';
        }
      } catch (\Exception $e) {
        $errors[] = 'Invalid date format';
      }
    }

    // Location validation
    if (!empty($data['location_type'])) {
      if (!in_array($data['location_type'], ['physical', 'online', 'hybrid'])) {
        $errors[] = 'Invalid location type';
      }

      if ($data['location_type'] === 'physical' && empty($data['location_address'])) {
        $errors[] = 'Physical location requires an address';
      }

      if (in_array($data['location_type'], ['online', 'hybrid']) && empty($data['location_url'])) {
        $errors[] = 'Online events require a URL';
      }
    }

    // RRULE validation for recurring events
    if (!empty($data['is_recurring']) && !empty($data['rrule'])) {
      $rruleErrors = RRuleProcessor::validateRRule($data['rrule']);
      $errors = array_merge($errors, $rruleErrors);
    }

    // Category validation
    if (!empty($data['category']) && !in_array($data['category'], ['workshop', 'stammtisch', 'practice', 'lecture', 'special'])) {
      $errors[] = 'Invalid event category';
    }

    // Difficulty level validation
    if (!empty($data['difficulty_level']) && !in_array($data['difficulty_level'], ['beginner', 'intermediate', 'advanced', 'all'])) {
      $errors[] = 'Invalid difficulty level';
    }

    // Max participants validation
    if (!empty($data['max_participants']) && (!is_numeric($data['max_participants']) || $data['max_participants'] < 1)) {
      $errors[] = 'Maximum participants must be a positive number';
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors
    ];
  }

  /**
   * Validate contact submission data
   */
  private function validateContactSubmission(array $data): array
  {
    $errors = [];

    // Required fields
    $required = [
      'name' => 'Name is required',
      'email' => 'Email is required',
      'subject' => 'Subject is required',
      'message' => 'Message is required',
      'consent_privacy' => 'Privacy consent is required'
    ];

    foreach ($required as $field => $message) {
      if (empty($data[$field])) {
        $errors[] = $message;
      }
    }

    // Email validation
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Invalid email address';
    }

    // Subject validation
    if (!empty($data['subject']) && !in_array($data['subject'], ['teilnahme', 'organisation', 'feedback', 'partnership', 'support', 'conduct', 'other'])) {
      $errors[] = 'Invalid subject';
    }

    // Message length validation
    if (!empty($data['message'])) {
      if (strlen($data['message']) < 10) {
        $errors[] = 'Message is too short (minimum 10 characters)';
      }
      if (strlen($data['message']) > 5000) {
        $errors[] = 'Message is too long (maximum 5000 characters)';
      }
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors
    ];
  }

  /**
   * Validate spam protection measures
   */
  private function validateSpamProtection(array $data): bool
  {
    // Honeypot field check
    if (!empty($data['honeypot'])) {
      return false;
    }

    // Timestamp check (form should take at least 3 seconds to fill)
    if (!empty($data['timestamp'])) {
      $timestamp = (int)$data['timestamp'];
      $elapsed = time() - $timestamp;

      if ($elapsed < 3 || $elapsed > 3600) { // Too fast or too slow
        return false;
      }
    }

    return true;
  }

  /**
   * Sanitize event submission data
   */
  private function sanitizeEventData(array $data): array
  {
    return [
      'title' => htmlspecialchars(trim($data['title']), ENT_QUOTES, 'UTF-8'),
      'description' => htmlspecialchars(trim($data['description']), ENT_QUOTES, 'UTF-8'),
      'start_datetime' => $data['start_datetime'],
      'end_datetime' => $data['end_datetime'],
      'timezone' => $data['timezone'] ?? 'Europe/Berlin',
      'is_all_day' => !empty($data['is_all_day']),
      'is_recurring' => !empty($data['is_recurring']),
      'rrule' => !empty($data['rrule']) ? trim($data['rrule']) : null,
      'location_type' => $data['location_type'],
      'location_name' => !empty($data['location_name']) ? htmlspecialchars(trim($data['location_name']), ENT_QUOTES, 'UTF-8') : '',
      'location_address' => !empty($data['location_address']) ? htmlspecialchars(trim($data['location_address']), ENT_QUOTES, 'UTF-8') : '',
      'location_url' => !empty($data['location_url']) ? filter_var(trim($data['location_url']), FILTER_SANITIZE_URL) : '',
      'location_instructions' => !empty($data['location_instructions']) ? htmlspecialchars(trim($data['location_instructions']), ENT_QUOTES, 'UTF-8') : '',
      'category' => $data['category'] ?? 'stammtisch',
      'difficulty_level' => $data['difficulty_level'] ?? 'all',
      'max_participants' => !empty($data['max_participants']) ? (int)$data['max_participants'] : null,
      'requirements' => !empty($data['requirements']) ? htmlspecialchars(trim($data['requirements']), ENT_QUOTES, 'UTF-8') : '',
      'safety_notes' => !empty($data['safety_notes']) ? htmlspecialchars(trim($data['safety_notes']), ENT_QUOTES, 'UTF-8') : '',
      'preparation_notes' => !empty($data['preparation_notes']) ? htmlspecialchars(trim($data['preparation_notes']), ENT_QUOTES, 'UTF-8') : '',
      'organizer_name' => htmlspecialchars(trim($data['organizer_name']), ENT_QUOTES, 'UTF-8'),
      'organizer_email' => filter_var(trim($data['organizer_email']), FILTER_SANITIZE_EMAIL),
      'organizer_bio' => !empty($data['organizer_bio']) ? htmlspecialchars(trim($data['organizer_bio']), ENT_QUOTES, 'UTF-8') : '',
      'tags' => !empty($data['tags']) && is_array($data['tags']) ? array_map('trim', $data['tags']) : [],
      'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
      'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
  }

  /**
   * Sanitize contact submission data
   */
  private function sanitizeContactData(array $data): array
  {
    return [
      'name' => htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8'),
      'email' => filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL),
      'subject' => $data['subject'],
      'message' => htmlspecialchars(trim($data['message']), ENT_QUOTES, 'UTF-8'),
      'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
      'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
      'referrer' => $_SERVER['HTTP_REFERER'] ?? ''
    ];
  }

  /**
   * Store event submission in database
   */
  private function storeEventSubmission(array $data): ?int
  {
    try {
      $sql = "INSERT INTO submissions (payload, status, created_at) VALUES (?, 'pending', NOW())";
      $payload = json_encode([
        'type' => 'event',
        'data' => $data
      ]);

      $result = Database::insert($sql, [$payload]);
      return $result === false ? null : (int)$result;
    } catch (\Exception $e) {
      error_log("Error storing event submission: " . $e->getMessage());
      return null;
    }
  }
  /**
   * Store contact submission in database
   */
  private function storeContactSubmission(array $data): ?int
  {
    try {
      $sql = "INSERT INTO contact_submissions (name, email, subject, message, ip_address, user_agent, referrer, submitted_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

      $result = Database::insert($sql, [
        $data['name'],
        $data['email'],
        $data['subject'],
        $data['message'],
        $data['ip_address'],
        $data['user_agent'],
        $data['referrer']
      ]);
      return $result === false ? null : (int)$result;
    } catch (\Exception $e) {
      error_log("Error storing contact submission: " . $e->getMessage());
      return null;
    }
  }


  /**
   * Check rate limiting
   */
  private function checkRateLimit(string $endpoint, int $maxRequests, int $timeWindow): bool
  {
    try {
      $ip = $_SERVER['REMOTE_ADDR'] ?? '';
      $windowStart = date('Y-m-d H:i:s', time() - $timeWindow);

      // Clean old entries
      $sql = "DELETE FROM rate_limits WHERE window_start < ?";
      Database::execute($sql, [$windowStart]);

      // Check current count
      $sql = "SELECT requests_count FROM rate_limits WHERE ip_address = ? AND endpoint = ?";
      $result = Database::fetchOne($sql, [$ip, $endpoint]);

      if ($result) {
        if ($result['requests_count'] >= $maxRequests) {
          return false;
        }

        // Increment count
        $sql = "UPDATE rate_limits SET requests_count = requests_count + 1 WHERE ip_address = ? AND endpoint = ?";
        Database::execute($sql, [$ip, $endpoint]);
      } else {
        // Create new entry
        $sql = "INSERT INTO rate_limits (ip_address, endpoint, requests_count, window_start) VALUES (?, ?, 1, NOW())";
        Database::execute($sql, [$ip, $endpoint]);
      }

      return true;
    } catch (\Exception $e) {
      error_log("Rate limit check error: " . $e->getMessage());
      return true; // Allow on error
    }
  }

  /**
   * Send event submission confirmation email
   */
  private function sendEventSubmissionConfirmation(array $data): void
  {
    try {
      $mail = $this->createMailer();

      $mail->addAddress($data['organizer_email'], $data['organizer_name']);
      $mail->Subject = 'Event-Einreichung erhalten - Hypnose Stammtisch';

      $mail->Body = $this->getEventConfirmationEmailTemplate($data);
      $mail->AltBody = strip_tags($mail->Body);

      $mail->send();
    } catch (\Exception $e) {
      error_log("Failed to send event confirmation email: " . $e->getMessage());
    }
  }

  /**
   * Send contact confirmation email
   */
  private function sendContactConfirmation(array $data): void
  {
    try {
      $mail = $this->createMailer();

      $mail->addAddress($data['email'], $data['name']);
      $mail->Subject = 'Nachricht erhalten - Hypnose Stammtisch';

      $mail->Body = $this->getContactConfirmationEmailTemplate($data);
      $mail->AltBody = strip_tags($mail->Body);

      $mail->send();
    } catch (\Exception $e) {
      error_log("Failed to send contact confirmation email: " . $e->getMessage());
    }
  }

  /**
   * Notify admins of new submission
   */
  private function notifyAdminsOfSubmission(int $submissionId, array $data): void
  {
    try {
      $adminEmail = Config::get('mail.admin_email');
      if (!$adminEmail) return;

      $mail = $this->createMailer();

      $mail->addAddress($adminEmail);
      $mail->Subject = 'Neue Event-Einreichung #' . $submissionId;

      $mail->Body = $this->getAdminNotificationTemplate('event', $submissionId, $data);
      $mail->AltBody = strip_tags($mail->Body);

      $mail->send();
    } catch (\Exception $e) {
      error_log("Failed to send admin notification: " . $e->getMessage());
    }
  }

  /**
   * Notify admins of new contact
   */
  private function notifyAdminsOfContact(int $submissionId, array $data): void
  {
    try {
      $adminEmail = Config::get('mail.admin_email');
      if (!$adminEmail) return;

      $mail = $this->createMailer();

      $mail->addAddress($adminEmail);
      $mail->Subject = 'Neue Kontaktanfrage #' . $submissionId . ' - ' . ucfirst($data['subject']);

      $mail->Body = $this->getAdminNotificationTemplate('contact', $submissionId, $data);
      $mail->AltBody = strip_tags($mail->Body);

      $mail->send();
    } catch (\Exception $e) {
      error_log("Failed to send admin contact notification: " . $e->getMessage());
    }
  }

  /**
   * Create PHPMailer instance
   */
  private function createMailer(): PHPMailer
  {
    $mail = new PHPMailer(true);

    // SMTP configuration
    if (Config::get('mail.smtp_enabled', false)) {
      $mail->isSMTP();
      $mail->Host = Config::get('mail.smtp_host');
      $mail->SMTPAuth = true;
      $mail->Username = Config::get('mail.smtp_username');
      $mail->Password = Config::get('mail.smtp_password');
      $mail->SMTPSecure = Config::get('mail.smtp_encryption', 'tls');
      $mail->Port = Config::get('mail.smtp_port', 587);
    }

    $mail->setFrom(
      Config::get('mail.from_email', 'noreply@hypnose-stammtisch.de'),
      Config::get('mail.from_name', 'Hypnose Stammtisch')
    );

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    return $mail;
  }

  /**
   * Get event confirmation email template
   */
  private function getEventConfirmationEmailTemplate(array $data): string
  {
    return "
        <h2>Vielen Dank für Ihre Event-Einreichung!</h2>

        <p>Liebe/r {$data['organizer_name']},</p>

        <p>wir haben Ihre Event-Einreichung erhalten und werden sie zeitnah prüfen.</p>

        <h3>Eingereichte Daten:</h3>
        <ul>
            <li><strong>Titel:</strong> {$data['title']}</li>
            <li><strong>Datum:</strong> " . date('d.m.Y H:i', strtotime($data['start_datetime'])) . "</li>
            <li><strong>Ort:</strong> " . ucfirst($data['location_type']) . "</li>
        </ul>

        <p>Sie erhalten eine weitere E-Mail, sobald Ihr Event freigeschaltet wurde.</p>

        <p>Bei Fragen können Sie sich jederzeit an uns wenden.</p>

        <p>Herzliche Grüße<br>
        Das Hypnose Stammtisch Team</p>
        ";
  }

  /**
   * Get contact confirmation email template
   */
  private function getContactConfirmationEmailTemplate(array $data): string
  {
    return "
        <h2>Nachricht erhalten</h2>

        <p>Liebe/r {$data['name']},</p>

        <p>vielen Dank für Ihre Nachricht. Wir haben sie erhalten und werden uns zeitnah bei Ihnen melden.</p>

        <h3>Ihre Nachricht:</h3>
        <p><strong>Betreff:</strong> " . ucfirst($data['subject']) . "</p>
        <p>{$data['message']}</p>

        <p>Herzliche Grüße<br>
        Das Hypnose Stammtisch Team</p>
        ";
  }

  /**
   * Get admin notification email template
   */
  private function getAdminNotificationTemplate(string $type, int $id, array $data): string
  {
    if ($type === 'event') {
      return "
            <h2>Neue Event-Einreichung #{$id}</h2>

            <h3>Event-Details:</h3>
            <ul>
                <li><strong>Titel:</strong> {$data['title']}</li>
                <li><strong>Organisator:</strong> {$data['organizer_name']} ({$data['organizer_email']})</li>
                <li><strong>Datum:</strong> " . date('d.m.Y H:i', strtotime($data['start_datetime'])) . " - " . date('d.m.Y H:i', strtotime($data['end_datetime'])) . "</li>
                <li><strong>Ort:</strong> " . ucfirst($data['location_type']) . "</li>
                <li><strong>Kategorie:</strong> " . ucfirst($data['category']) . "</li>
            </ul>

            <h3>Beschreibung:</h3>
            <p>{$data['description']}</p>

            <p><a href='" . Config::get('app.url') . "/admin/submissions/{$id}'>Einreichung prüfen</a></p>
            ";
    } else {
      return "
            <h2>Neue Kontaktanfrage #{$id}</h2>

            <h3>Kontakt-Details:</h3>
            <ul>
                <li><strong>Name:</strong> {$data['name']}</li>
                <li><strong>E-Mail:</strong> {$data['email']}</li>
                <li><strong>Betreff:</strong> " . ucfirst($data['subject']) . "</li>
            </ul>

            <h3>Nachricht:</h3>
            <p>{$data['message']}</p>

            <p><a href='" . Config::get('app.url') . "/admin/contacts/{$id}'>Nachricht bearbeiten</a></p>
            ";
    }
  }
}
