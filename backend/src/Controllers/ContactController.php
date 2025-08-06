<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\Validator;
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
                Response::error('Message flagged as spam', 400);
                return;
            }

            // Rate limiting check
            if (!$this->checkRateLimit()) {
                Response::error('Too many requests. Please try again later.', 429);
                return;
            }

            // Sanitize input
            $data = [
                'name' => Validator::sanitizeString($input['name']),
                'email' => Validator::sanitizeEmail($input['email']),
                'subject' => $input['subject'],
                'message' => Validator::sanitizeString($input['message']),
                'ip_address' => $this->getClientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referrer' => $_SERVER['HTTP_REFERER'] ?? ''
            ];

            // Save to database
            $contactId = $this->saveContactSubmission($data);

            if (!$contactId) {
                Response::error('Failed to save contact submission', 500);
                return;
            }

            // Send email notification
            $this->sendEmailNotification($data, $contactId);

            Response::success(null, 'Ihre Nachricht wurde erfolgreich gesendet. Wir werden uns bald bei Ihnen melden.');

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
        return "
        Neue Kontaktanfrage #{$contactId}

        Von: {$data['name']} <{$data['email']}>
        Betreff: {$data['subject']}
        IP-Adresse: {$data['ip_address']}
        Zeit: " . date('Y-m-d H:i:s') . "

        Nachricht:
        " . $data['message'] . "

        ---
        Diese E-Mail wurde automatisch vom Hypnose Stammtisch Kontaktformular generiert.
        ";
    }

    /**
     * Send mail (basic implementation)
     */
    private function sendMail(string $to, string $subject, string $body, string $replyTo = '', string $replyToName = ''): bool
    {
        $headers = [
            'From: ' . Config::get('mail.from_name') . ' <' . Config::get('mail.from_address') . '>',
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: Hypnose Stammtisch Backend'
        ];

        if ($replyTo) {
            $headers[] = 'Reply-To: ' . ($replyToName ? "{$replyToName} <{$replyTo}>" : $replyTo);
        }

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }

    /**
     * Check rate limiting
     */
    private function checkRateLimit(): bool
    {
        $ip = $this->getClientIp();
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
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
