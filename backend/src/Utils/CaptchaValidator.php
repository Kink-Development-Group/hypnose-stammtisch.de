<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use HypnoseStammtisch\Config\Config;

/**
 * CAPTCHA Validator Service
 *
 * Supports Cloudflare Turnstile (default), hCaptcha, and reCAPTCHA v3 for invisible CAPTCHA protection.
 * Turnstile is recommended as it's privacy-friendly and free.
 *
 * @package HypnoseStammtisch\Utils
 */
class CaptchaValidator
{
    /**
     * Supported CAPTCHA providers
     */
    public const PROVIDER_TURNSTILE = 'turnstile';
    public const PROVIDER_HCAPTCHA = 'hcaptcha';
    public const PROVIDER_RECAPTCHA = 'recaptcha';

    /**
     * Verification endpoints for each provider
     */
    private const VERIFICATION_URLS = [
        self::PROVIDER_TURNSTILE => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        self::PROVIDER_HCAPTCHA => 'https://hcaptcha.com/siteverify',
        self::PROVIDER_RECAPTCHA => 'https://www.google.com/recaptcha/api/siteverify',
    ];

    /**
     * Minimum score threshold for reCAPTCHA v3 (0.0 - 1.0)
     * Higher values are more strict (less false negatives, more false positives)
     */
    private const RECAPTCHA_SCORE_THRESHOLD = 0.5;

    /**
     * Check if CAPTCHA validation is enabled
     */
    public static function isEnabled(): bool
    {
        return Config::get('captcha.enabled', false) === true;
    }

    /**
     * Get the configured CAPTCHA provider
     */
    public static function getProvider(): string
    {
        return Config::get('captcha.provider', self::PROVIDER_TURNSTILE);
    }

    /**
     * Get the site key for frontend usage
     */
    public static function getSiteKey(): ?string
    {
        return Config::get('captcha.site_key');
    }

    /**
     * Validate a CAPTCHA response token
     *
     * @param string|null $token The CAPTCHA response token from the client
     * @param string|null $expectedAction Optional action name (for reCAPTCHA v3)
     * @return array{success: bool, message: string, score?: float, action?: string}
     */
    public static function validate(?string $token, ?string $expectedAction = null): array
    {
        // Skip validation if disabled (development mode)
        if (!self::isEnabled()) {
            AuditLogger::log('captcha.skipped', 'security', null, [
                'reason' => 'disabled_in_config'
            ]);
            return [
                'success' => true,
                'message' => 'CAPTCHA validation skipped (disabled)'
            ];
        }

        // Token is required when CAPTCHA is enabled
        if (empty($token)) {
            AuditLogger::log('captcha.missing_token', 'security', IpBanManager::getClientIP());
            return [
                'success' => false,
                'message' => 'CAPTCHA verification required'
            ];
        }

        $provider = self::getProvider();
        $secretKey = Config::get('captcha.secret_key');

        if (empty($secretKey)) {
            error_log('CAPTCHA secret key not configured');
            // Fail open with warning in development, fail closed in production
            if (Config::get('app.environment') === 'development') {
                return [
                    'success' => true,
                    'message' => 'CAPTCHA validation skipped (no secret key configured)'
                ];
            }
            return [
                'success' => false,
                'message' => 'CAPTCHA configuration error'
            ];
        }

        return self::verifyToken($provider, $secretKey, $token, $expectedAction);
    }

    /**
     * Verify token with the CAPTCHA provider
     */
    private static function verifyToken(
        string $provider,
        string $secretKey,
        string $token,
        ?string $expectedAction = null
    ): array {
        $url = self::VERIFICATION_URLS[$provider] ?? null;

        if (!$url) {
            return [
                'success' => false,
                'message' => 'Invalid CAPTCHA provider configured'
            ];
        }

        $clientIp = IpBanManager::getClientIP();

        // Build request parameters
        $params = [
            'secret' => $secretKey,
            'response' => $token,
        ];

        // Include remote IP for additional verification (optional but recommended)
        if ($provider === self::PROVIDER_TURNSTILE) {
            $params['remoteip'] = $clientIp;
        } elseif ($provider === self::PROVIDER_HCAPTCHA) {
            $params['remoteip'] = $clientIp;
        } elseif ($provider === self::PROVIDER_RECAPTCHA) {
            $params['remoteip'] = $clientIp;
        }

        try {
            $response = self::makeHttpRequest($url, $params);

            if ($response === null) {
                AuditLogger::log('captcha.verification_failed', 'security', $clientIp, [
                    'provider' => $provider,
                    'error' => 'HTTP request failed'
                ]);
                return [
                    'success' => false,
                    'message' => 'CAPTCHA verification failed - please try again'
                ];
            }

            return self::processResponse($provider, $response, $expectedAction, $clientIp);
        } catch (\Exception $e) {
            error_log('CAPTCHA verification error: ' . $e->getMessage());
            AuditLogger::log('captcha.error', 'security', $clientIp, [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'CAPTCHA verification error - please try again'
            ];
        }
    }

    /**
     * Make HTTP POST request to verification endpoint
     */
    private static function makeHttpRequest(string $url, array $params): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($params),
                'timeout' => 10,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }

    /**
     * Process the verification response based on provider
     */
    private static function processResponse(
        string $provider,
        array $response,
        ?string $expectedAction,
        string $clientIp
    ): array {
        $success = $response['success'] ?? false;

        if (!$success) {
            $errorCodes = $response['error-codes'] ?? [];
            AuditLogger::log('captcha.failed', 'security', $clientIp, [
                'provider' => $provider,
                'error_codes' => $errorCodes
            ]);
            return [
                'success' => false,
                'message' => self::getErrorMessage($provider, $errorCodes)
            ];
        }

        // Additional validation for reCAPTCHA v3 (score-based)
        if ($provider === self::PROVIDER_RECAPTCHA) {
            $score = $response['score'] ?? 0.0;
            $action = $response['action'] ?? null;

            // Check score threshold
            if ($score < self::RECAPTCHA_SCORE_THRESHOLD) {
                AuditLogger::log('captcha.low_score', 'security', $clientIp, [
                    'provider' => $provider,
                    'score' => $score,
                    'threshold' => self::RECAPTCHA_SCORE_THRESHOLD
                ]);
                return [
                    'success' => false,
                    'message' => 'CAPTCHA verification failed - suspicious activity detected',
                    'score' => $score
                ];
            }

            // Verify action if expected
            if ($expectedAction !== null && $action !== $expectedAction) {
                AuditLogger::log('captcha.action_mismatch', 'security', $clientIp, [
                    'provider' => $provider,
                    'expected_action' => $expectedAction,
                    'actual_action' => $action
                ]);
                return [
                    'success' => false,
                    'message' => 'CAPTCHA verification failed - action mismatch',
                    'score' => $score,
                    'action' => $action
                ];
            }

            return [
                'success' => true,
                'message' => 'CAPTCHA verified successfully',
                'score' => $score,
                'action' => $action
            ];
        }

        // Turnstile and hCaptcha success
        AuditLogger::log('captcha.success', 'security', $clientIp, [
            'provider' => $provider
        ]);

        return [
            'success' => true,
            'message' => 'CAPTCHA verified successfully'
        ];
    }

    /**
     * Get human-readable error message from error codes
     */
    private static function getErrorMessage(string $provider, array $errorCodes): string
    {
        if (empty($errorCodes)) {
            return 'CAPTCHA verification failed - please try again';
        }

        // Common error codes across providers
        $errorMessages = [
            // Turnstile error codes
            'missing-input-secret' => 'Server configuration error',
            'invalid-input-secret' => 'Server configuration error',
            'missing-input-response' => 'Please complete the CAPTCHA challenge',
            'invalid-input-response' => 'Invalid CAPTCHA response - please try again',
            'invalid-widget-id' => 'Server configuration error',
            'invalid-parsed-secret' => 'Server configuration error',
            'bad-request' => 'Invalid request - please try again',
            'timeout-or-duplicate' => 'CAPTCHA expired - please try again',
            // hCaptcha error codes
            'invalid-or-already-seen-response' => 'CAPTCHA expired - please try again',
            'not-using-dummy-passcode' => 'Server configuration error',
            'sitekey-secret-mismatch' => 'Server configuration error',
            // reCAPTCHA error codes
            'invalid-keys' => 'Server configuration error',
            'timeout-or-duplicate' => 'CAPTCHA expired - please try again',
        ];

        foreach ($errorCodes as $code) {
            if (isset($errorMessages[$code])) {
                return $errorMessages[$code];
            }
        }

        return 'CAPTCHA verification failed - please try again';
    }

    /**
     * Get CAPTCHA configuration for frontend
     * Returns only public information (site key, provider)
     */
    public static function getClientConfig(): array
    {
        if (!self::isEnabled()) {
            return [
                'enabled' => false
            ];
        }

        return [
            'enabled' => true,
            'provider' => self::getProvider(),
            'siteKey' => self::getSiteKey()
        ];
    }
}
