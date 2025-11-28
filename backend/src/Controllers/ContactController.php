<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\Validator;
use HypnoseStammtisch\Utils\IpBanManager;
use HypnoseStammtisch\Utils\AuditLogger;
use HypnoseStammtisch\Config\Config;

/**
 * Contact form controller
 */
class ContactController
{
    /**
     * Handle contact form submission
     * POST /api/contact
     */
    public function submit(): void
    {
        try {
            // Get client IP using secure method
            $clientIp = IpBanManager::getClientIP();

            // Check if IP is banned
            if (IpBanManager::isIPBanned($clientIp)) {
                AuditLogger::log('contact.blocked_ip', 'ip_ban', $clientIp, ['endpoint' => 'contact']);
                Response::error('Your request could not be processed. Please try again later.', 403);
                return;
            }

            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                Response::error('Invalid JSON data', 400);
                return;
            }

            // Validate input
            $errors = Validator::validateContactForm($input);

            if (!empty($errors)) {
                Response::error('Validation failed', 400, $errors);
                return;
            }

            // Check for spam
            if (Validator::isSpam($input)) {
                AuditLogger::log('contact.spam_detected', 'spam', $clientIp);
                Response::error('Message flagged as spam', 400);
                return;
            }

            // Rate limiting check (fail closed for security)
            if (!$this->checkRateLimit($clientIp)) {
                Response::error('Too many requests. Please try again later.', 429);
                return;
            }

            // Sanitize input - use secure IP handling
            $data = [
                'name' => Validator::sanitizeString($input['name']),
                'email' => Validator::sanitizeEmail($input['email']),
                'subject' => $input['subject'],
                'message' => Validator::sanitizeString($input['message']),
                'ip_address' => $clientIp,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referrer' => $_SERVER['HTTP_REFERER'] ?? ''
            ];

            // Try to save to database, but don't fail if DB is not available (development mode)
            $contactId = $this->saveContactSubmission($data);

            if (!$contactId) {
                // In development mode, simulate successful submission
                error_log("Contact submission error: Failed to store contact submission");
                $contactId = 'dev_' . uniqid(); // Generate fake ID for development
            }

            // Send email notification (will fail gracefully in development)
            $this->sendEmailNotification($data, $contactId);

            AuditLogger::log('contact.submitted', 'contact', $contactId, [
                'ip' => $clientIp,
                'subject' => $data['subject']
            ]);

            Response::success([
                'id' => $contactId,
                'timestamp' => date('c')
            ], 'Ihre Nachricht wurde erfolgreich gesendet. Wir werden uns bald bei Ihnen melden.');
        } catch (\Exception $e) {
            error_log("Contact form error: " . $e->getMessage());
            Response::error('Internal server error', 500);
        }
    }

    /**
     * Save contact submission to database
     */
    private function saveContactSubmission(array $data): string|false
    {
        try {
            $sql = "INSERT INTO contact_submissions
                    (name, email, subject, message, ip_address, user_agent, referrer)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            return Database::insert($sql, [
                $data['name'],
                $data['email'],
                $data['subject'],
                $data['message'],
                $data['ip_address'],
                $data['user_agent'],
                $data['referrer']
            ]);
        } catch (\Exception $e) {
            error_log("Error storing contact submission: " . $e->getMessage());
            return false; // Will be handled gracefully in submit() method
        }
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(array $data, string $contactId): void
    {
        try {
            // Get recipient based on subject
            $recipients = [
                'teilnahme' => Config::get('contact.events'),
                'organisation' => Config::get('contact.events'),
                'feedback' => Config::get('contact.general'),
                'partnership' => Config::get('contact.general'),
                'support' => Config::get('contact.support'),
                'conduct' => Config::get('contact.conduct'),
                'other' => Config::get('contact.general')
            ];

            $recipient = $recipients[$data['subject']] ?? Config::get('contact.general');

            // Email subject
            $subjectTexts = [
                'teilnahme' => 'Neue Anfrage zur Event-Teilnahme',
                'organisation' => 'Neue Anfrage zur Event-Organisation',
                'feedback' => 'Neues Feedback',
                'partnership' => 'Neue Partnerschaftsanfrage',
                'support' => 'Neue Support-Anfrage',
                'conduct' => 'Neue Verhaltenskodex-Anfrage',
                'other' => 'Neue Kontaktanfrage'
            ];

            $emailSubject = $subjectTexts[$data['subject']] ?? 'Neue Kontaktanfrage';

            // Email body
            $emailBody = $this->generateEmailBody($data, $contactId);

            // Send email (would need PHPMailer configuration)
            $this->sendMail($recipient, $emailSubject, $emailBody, $data['email'], $data['name']);
        } catch (\Exception $e) {
            error_log("Failed to send email notification: " . $e->getMessage());
            // Don't fail the request if email fails
        }
    }

    /**
     * Generate email body
     */
    private function generateEmailBody(array $data, string $contactId): string
    {
        $appName = Config::get('app.name', 'Hypnose Stammtisch');

        return "
        Neue Kontaktanfrage #{$contactId}

        Von: {$data['name']} <{$data['email']}>
        Betreff: {$data['subject']}
        IP-Adresse: {$data['ip_address']}
        Zeit: " . date('Y-m-d H:i:s') . "

        Nachricht:
        " . $data['message'] . "

        ---
        Diese E-Mail wurde automatisch vom {$appName} Kontaktformular generiert.
        ";
    }

    /**
     * Send mail (basic implementation)
     */
    private function sendMail(string $to, string $subject, string $body, string $replyTo = '', string $replyToName = ''): bool
    {
        $appName = Config::get('app.name', 'Hypnose Stammtisch');
        $fromName = Config::get('mail.from_name', $appName);

        $headers = [
            'From: ' . $fromName . ' <' . Config::get('mail.from_address') . '>',
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: ' . $appName . ' Backend'
        ];

        if ($replyTo) {
            $headers[] = 'Reply-To: ' . ($replyToName ? "{$replyToName} <{$replyTo}>" : $replyTo);
        }

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }

    /**
     * Check rate limiting
     * Returns false if rate limit exceeded or on error (fail closed for security)
     */
    private function checkRateLimit(string $clientIp = ''): bool
    {
        try {
            // Use provided IP or get securely
            $ip = $clientIp ?: IpBanManager::getClientIP();
            if (empty($ip) || $ip === '0.0.0.0' || $ip === '::') {
                // Cannot identify client - fail closed for security
                error_log("Rate limit check: Unable to determine client IP");
                return false;
            }
            
            $endpoint = 'contact';
            $maxRequests = Config::get('rate_limit.requests', 10);
            $window = Config::get('rate_limit.window', 3600);

            // Clean old entries
            Database::execute(
                "DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)",
                [$window]
            );

            // Check current count
            $current = Database::fetchOne(
                "SELECT requests_count FROM rate_limits WHERE ip_address = ? AND endpoint = ?",
                [$ip, $endpoint]
            );

            if ($current) {
                if ($current['requests_count'] >= $maxRequests) {
                    AuditLogger::log('contact.rate_limited', 'rate_limit', $ip, [
                        'endpoint' => $endpoint,
                        'requests_count' => $current['requests_count'],
                        'max_requests' => $maxRequests
                    ]);
                    return false;
                }

                // Increment counter
                Database::execute(
                    "UPDATE rate_limits SET requests_count = requests_count + 1 WHERE ip_address = ? AND endpoint = ?",
                    [$ip, $endpoint]
                );
            } else {
                // Create new rate limit entry
                Database::execute(
                    "INSERT INTO rate_limits (ip_address, endpoint, requests_count) VALUES (?, ?, 1)",
                    [$ip, $endpoint]
                );
            }

            return true;
        } catch (\Exception $e) {
            // SECURITY FIX: Fail closed instead of open
            // If rate limiting fails, block the request to prevent abuse
            error_log("Rate limit check error (blocking request): " . $e->getMessage());
            return false;
        }
    }
}
