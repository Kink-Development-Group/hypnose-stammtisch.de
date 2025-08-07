<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

/**
 * Validation utility class
 */
class Validator
{
    private array $data;
    private array $errors = [];

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

        // Email validation
        if (!self::isRequired($data['email'] ?? '')) {
            $errors['email'] = 'E-Mail ist erforderlich';
        } elseif (!self::isValidEmail($data['email'])) {
            $errors['email'] = 'Ungültige E-Mail-Adresse';
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
     */
    public static function isSpam(array $data): bool
    {
        // Simple spam detection
        $message = $data['message'] ?? '';

        // Check for excessive links
        if (preg_match_all('/https?:\/\//', $message) > 3) {
            return true;
        }

        // Check for suspicious keywords
        $spamKeywords = [
            'viagra',
            'casino',
            'lottery',
            'winner',
            'congratulations',
            'click here',
            'free money',
            'guaranteed',
            'investment'
        ];

        $lowerMessage = strtolower($message);
        foreach ($spamKeywords as $keyword) {
            if (strpos($lowerMessage, $keyword) !== false) {
                return true;
            }
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
