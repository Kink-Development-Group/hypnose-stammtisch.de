<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use HypnoseStammtisch\Config\Config;

/**
 * Validation utility class
 */
class Validator
{
    private array $data;
    private array $errors = [];

    /**
     * Default list of disposable email domains
     * Can be overridden via config 'security.disposable_email_domains'
     */
    private const DEFAULT_DISPOSABLE_DOMAINS = [
        'mailinator.com',
        'guerrillamail.com',
        'tempmail.com',
        'temp-mail.org',
        'throwaway.email',
        '10minutemail.com',
        'fakeinbox.com',
        'trashmail.com',
        'mailnator.com',
        'yopmail.com',
        'getnada.com',
        'getairmail.com',
        'dispostable.com',
        'mintemail.com',
        'tempail.com',
        'fakemailgenerator.com',
        'emailondeck.com',
        'mohmal.com',
        'tempr.email',
        'discard.email',
        'maildrop.cc',
        'guerrillamail.info',
        'sharklasers.com',
        'grr.la',
        'mailcatch.com',
        'mailnesia.com',
        'spamgourmet.com',
        'tempmailaddress.com',
        'burnermail.io',
        'inboxkitten.com',
        'emkei.cz',
        'anonymbox.com',
        'tempinbox.com',
        'fakemailgenerator.net',
        'temporary-mail.net'
    ];

    /**
     * Spam detection thresholds
     */
    private const MAX_LINKS_ALLOWED = 2;
    private const MIN_TEXT_WITHOUT_LINKS = 20;
    private const MIN_LETTERS_FOR_CAPS_CHECK = 20;
    private const MAX_CAPS_RATIO = 0.5;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Check if validation passed
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Validate required fields
     */
    public function required(array $fields): self
    {
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || !self::isRequired($this->data[$field])) {
                $this->errors[$field] = "Field '{$field}' is required";
            }
        }
        return $this;
    }

    /**
     * Validate email field
     */
    public function email(string $field): self
    {
        if (isset($this->data[$field]) && !self::isValidEmail($this->data[$field])) {
            $this->errors[$field] = "Field '{$field}' must be a valid email address";
        }
        return $this;
    }

    /**
     * Validate length of field
     */
    public function length(string $field, int $min, int $max = null): self
    {
        if (isset($this->data[$field]) && !self::isValidLength($this->data[$field], $min, $max)) {
            $maxText = $max ? " and max {$max}" : '';
            $this->errors[$field] = "Field '{$field}' must be at least {$min}{$maxText} characters";
        }
        return $this;
    }

    /**
     * Validate email address
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate date format (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
     */
    public static function isValidDate(string $date): bool
    {
        // Try different date formats
        $formats = ['Y-m-d', 'Y-m-d H:i:s', 'd.m.Y', 'd.m.Y H:i:s'];

        foreach ($formats as $format) {
            $dateTime = \DateTime::createFromFormat($format, $date);
            if ($dateTime && $dateTime->format($format) === $date) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate URL
     */
    public static function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate phone number (basic validation)
     */
    public static function isValidPhone(string $phone): bool
    {
        $phone = preg_replace('/[^\d+]/', '', $phone);
        return preg_match('/^[\+]?[\d\s\-\(\)]{7,20}$/', $phone) === 1;
    }

    /**
     * Validate string length
     */
    public static function isValidLength(string $value, int $min, int $max = null): bool
    {
        $length = mb_strlen($value, 'UTF-8');

        if ($length < $min) {
            return false;
        }

        if ($max !== null && $length > $max) {
            return false;
        }

        return true;
    }

    /**
     * Validate required field
     */
    public static function isRequired(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_array($value) && empty($value)) {
            return false;
        }

        return true;
    }

    /**
     * Validate integer
     */
    public static function isValidInteger(mixed $value, int $min = null, int $max = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $intValue = (int)$value;

        if ($min !== null && $intValue < $min) {
            return false;
        }

        if ($max !== null && $intValue > $max) {
            return false;
        }

        return true;
    }

    /**
     * Validate enum value
     */
    public static function isValidEnum(string $value, array $allowedValues): bool
    {
        return in_array($value, $allowedValues, true);
    }

    /**
     * Sanitize string input
     */
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize email
     */
    public static function sanitizeEmail(string $email): string
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize URL
     */
    public static function sanitizeUrl(string $url): string
    {
        return filter_var(trim($url), FILTER_SANITIZE_URL);
    }

    /**
     * Validate contact form data
     */
    public static function validateContactForm(array $data): array
    {
        $errors = [];

        // Name validation
        if (!self::isRequired($data['name'] ?? '')) {
            $errors['name'] = 'Name ist erforderlich';
        } elseif (!self::isValidLength($data['name'], 2, 255)) {
            $errors['name'] = 'Name muss zwischen 2 und 255 Zeichen lang sein';
        }

        // Email validation with domain check
        if (!self::isRequired($data['email'] ?? '')) {
            $errors['email'] = 'E-Mail ist erforderlich';
        } elseif (!self::isValidEmail($data['email'])) {
            $errors['email'] = 'Ungültige E-Mail-Adresse';
        } elseif (!self::isValidEmailDomain($data['email'])) {
            $errors['email'] = 'Bitte verwenden Sie eine gültige E-Mail-Adresse (keine Wegwerf-E-Mail)';
        }

        // Subject validation
        $validSubjects = ['teilnahme', 'organisation', 'feedback', 'partnership', 'support', 'conduct', 'other'];
        if (!self::isRequired($data['subject'] ?? '')) {
            $errors['subject'] = 'Betreff ist erforderlich';
        } elseif (!self::isValidEnum($data['subject'], $validSubjects)) {
            $errors['subject'] = 'Ungültiger Betreff';
        }

        // Message validation
        if (!self::isRequired($data['message'] ?? '')) {
            $errors['message'] = 'Nachricht ist erforderlich';
        } elseif (!self::isValidLength($data['message'], 10, 5000)) {
            $errors['message'] = 'Nachricht muss zwischen 10 und 5000 Zeichen lang sein';
        }

        // Consent validation
        if (!isset($data['consent']) || !$data['consent']) {
            $errors['consent'] = 'Zustimmung zur Datenverarbeitung ist erforderlich';
        }

        return $errors;
    }

    /**
     * Validate email domain - checks for disposable emails and DNS records
     */
    public static function isValidEmailDomain(string $email): bool
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false;
        }

        $domain = strtolower($parts[1]);

        // Get disposable domains from config or use defaults
        $disposableDomains = Config::get('security.disposable_email_domains') ?? self::DEFAULT_DISPOSABLE_DOMAINS;

        if (in_array($domain, $disposableDomains, true)) {
            return false;
        }

        // Check if domain has MX records (can receive email)
        // This check can be disabled via config for performance
        $enableDnsCheck = Config::get('security.enable_email_dns_check', true);

        if ($enableDnsCheck && function_exists('checkdnsrr')) {
            // Cache DNS results to reduce latency on repeated checks
            static $dnsCache = [];

            if (!isset($dnsCache[$domain])) {
                // Check for MX records first, then A records as fallback
                $dnsCache[$domain] = checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
            }

            if (!$dnsCache[$domain]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate event registration data
     */
    public static function validateEventRegistration(array $data): array
    {
        $errors = [];

        // Name validation
        if (!self::isRequired($data['name'] ?? '')) {
            $errors['name'] = 'Name ist erforderlich';
        } elseif (!self::isValidLength($data['name'], 2, 255)) {
            $errors['name'] = 'Name muss zwischen 2 und 255 Zeichen lang sein';
        }

        // Email validation
        if (!self::isRequired($data['email'] ?? '')) {
            $errors['email'] = 'E-Mail ist erforderlich';
        } elseif (!self::isValidEmail($data['email'])) {
            $errors['email'] = 'Ungültige E-Mail-Adresse';
        }

        // Phone validation (optional)
        if (!empty($data['phone']) && !self::isValidPhone($data['phone'])) {
            $errors['phone'] = 'Ungültige Telefonnummer';
        }

        // Experience level validation
        $validLevels = ['none', 'beginner', 'intermediate', 'advanced'];
        if (!empty($data['experience_level']) && !self::isValidEnum($data['experience_level'], $validLevels)) {
            $errors['experience_level'] = 'Ungültiges Erfahrungslevel';
        }

        // Consent validation
        if (!isset($data['consent_data_processing']) || !$data['consent_data_processing']) {
            $errors['consent_data_processing'] = 'Zustimmung zur Datenverarbeitung ist erforderlich';
        }

        if (!isset($data['accepted_code_of_conduct']) || !$data['accepted_code_of_conduct']) {
            $errors['accepted_code_of_conduct'] = 'Akzeptierung des Verhaltenskodex ist erforderlich';
        }

        return $errors;
    }

    /**
     * Check for potential spam indicators
     * Enhanced spam detection with more patterns and checks
     */
    public static function isSpam(array $data): bool
    {
        $message = $data['message'] ?? '';
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';

        // Check for excessive links
        $linkCount = preg_match_all('/https?:\/\//i', $message);
        if ($linkCount !== false && $linkCount > self::MAX_LINKS_ALLOWED) {
            return true;
        }

        // Check for link-only message
        $textWithoutLinks = preg_replace('/https?:\/\/[^\s]+/', '', $message);
        if (strlen(trim($textWithoutLinks)) < self::MIN_TEXT_WITHOUT_LINKS && preg_match('/https?:\/\//', $message)) {
            return true;
        }

        // Check for suspicious keywords (expanded list)
        $spamKeywords = [
            'viagra',
            'cialis',
            'casino',
            'lottery',
            'winner',
            'congratulations',
            'click here',
            'free money',
            'guaranteed',
            'investment opportunity',
            'buy now',
            'limited time',
            'act now',
            'urgent',
            'make money fast',
            'million dollars',
            'bitcoin investment',
            'crypto opportunity',
            'work from home',
            'easy money',
            'risk free',
            'no obligation',
            'double your money',
            'earn extra cash',
            'be your own boss',
            'financial freedom',
            'passive income',
            'mlm',
            'multi-level marketing',
            'pyramid scheme',
            'adult content',
            'xxx',
            'porn',
            'sex video',
            'replica watches',
            'cheap meds',
            'online pharmacy',
            'weight loss',
            'diet pills',
            'enhancement pills',
            'enlarge',
            'medication without',
            'prescription free',
            'herbal remedy',
            'miracle cure'
        ];

        $lowerMessage = strtolower($message);
        $lowerName = strtolower($name);

        // Check message for spam keywords
        foreach ($spamKeywords as $keyword) {
            if (strpos($lowerMessage, $keyword) !== false) {
                return true;
            }
        }

        // Check name for spam patterns
        if (preg_match('/https?:\/\//', $name)) {
            return true;
        }

        // Check for obviously fake names (only special chars or numbers)
        // Using Unicode letter property \p{L} to support international names
        if (preg_match('/^[^\p{L}]+$/u', $name)) {
            return true;
        }

        // Check for excessive repetition (spam bots often repeat characters)
        if (preg_match('/(.)\1{4,}/', $message)) {
            return true;
        }

        // Check for excessive capitalization
        $upperCount = preg_match_all('/[A-Z]/', $message);
        $letterCount = preg_match_all('/[a-zA-Z]/', $message);
        if ($letterCount > self::MIN_LETTERS_FOR_CAPS_CHECK && $letterCount > 0 && ($upperCount / $letterCount) > self::MAX_CAPS_RATIO) {
            return true;
        }

        // Check for HTML/script injection attempts
        if (preg_match('/<\s*script|javascript:|onclick|onerror|onload/i', $message)) {
            return true;
        }

        return false;
    }

    /**
     * Generate secure token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken(string $token, string $sessionToken): bool
    {
        return hash_equals($sessionToken, $token);
    }
}
