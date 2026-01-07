<?php

return [
  'app' => [
    'name' => $_ENV['APP_NAME'] ?? 'Hypnose Stammtisch',
    'version' => '1.0.0',
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'base_url' => $_ENV['BASE_URL'] ?? '',
  ],

  'session' => [
    'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 86400),
    'name' => 'hypnose_session',
  ],

  // CAPTCHA Configuration (Cloudflare Turnstile, hCaptcha, or reCAPTCHA v3)
  'captcha' => [
    'enabled' => filter_var($_ENV['CAPTCHA_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'provider' => $_ENV['CAPTCHA_PROVIDER'] ?? 'turnstile', // turnstile, hcaptcha, recaptcha
    'site_key' => $_ENV['CAPTCHA_SITE_KEY'] ?? null,
    'secret_key' => $_ENV['CAPTCHA_SECRET_KEY'] ?? null,
  ],

  'security' => [
    'csrf_token_lifetime' => (int)($_ENV['CSRF_TOKEN_LIFETIME'] ?? 3600),
    'password_min_length' => 8,

    // Failed login protection
    'max_failed_attempts' => (int)($_ENV['MAX_FAILED_ATTEMPTS'] ?? 5),
    'time_window_seconds' => (int)($_ENV['TIME_WINDOW_SECONDS'] ?? 900), // 15 minutes
    'ip_ban_duration_seconds' => (int)($_ENV['IP_BAN_DURATION_SECONDS'] ?? 3600), // 1 hour, 0 = permanent
    'account_lock_duration_seconds' => (int)($_ENV['ACCOUNT_LOCK_DURATION_SECONDS'] ?? 3600), // 1 hour, 0 = manual unlock
    'head_admin_role_name' => $_ENV['HEAD_ADMIN_ROLE_NAME'] ?? 'head',

    // IP validation and proxy settings
    // In development, automatically allow private IPs (127.0.0.1, etc.)
    'allow_private_ips' => filter_var(
      $_ENV['ALLOW_PRIVATE_IPS'] ?? (in_array($_ENV['APP_ENV'] ?? 'production', ['development', 'local', 'dev']) ? 'true' : 'false'),
      FILTER_VALIDATE_BOOLEAN
    ),
    'trusted_proxies' => array_filter(array_map('trim', explode(',', $_ENV['TRUSTED_PROXIES'] ?? ''))),

    // Email validation settings
    // Enable/disable DNS MX record checking for email validation (may add latency)
    'enable_email_dns_check' => filter_var($_ENV['ENABLE_EMAIL_DNS_CHECK'] ?? true, FILTER_VALIDATE_BOOLEAN),

    // Disposable email domains can be extended via ENV (comma-separated list)
    // If not set, the default list in Validator::DEFAULT_DISPOSABLE_DOMAINS is used
    'disposable_email_domains' => !empty($_ENV['DISPOSABLE_EMAIL_DOMAINS'])
      ? array_filter(array_map('trim', explode(',', $_ENV['DISPOSABLE_EMAIL_DOMAINS'])))
      : null,

    // Audit log rate limiting for security events
    'audit_log_rate_limit' => [
      'untrusted_proxy_header' => [
        'max_logs' => (int)($_ENV['AUDIT_UNTRUSTED_PROXY_MAX_LOGS'] ?? 10),
        'period_seconds' => (int)($_ENV['AUDIT_UNTRUSTED_PROXY_PERIOD'] ?? 300), // 5 minutes
      ],
    ],
  ],

  'calendar' => [
    'cache_duration' => (int)($_ENV['CALENDAR_CACHE_DURATION'] ?? 3600),
  ],

  'cors' => [
    'allowed_origins' => [
      'http://localhost:5173',
      'http://localhost:3000',
      'http://127.0.0.1:5173',
      'http://127.0.0.1:3000',
      $_ENV['BASE_URL'] ?? '',
    ],
    'allowed_methods' => [
      'GET',
      'POST',
      'PUT',
      'PATCH',
      'DELETE',
      'OPTIONS',
    ],
    'allowed_headers' => [
      'Content-Type',
      'Authorization',
      'X-Requested-With',
      'Accept',
      'Origin',
      'Cache-Control',
      'X-CSRF-Token',
    ],
  ],
];
