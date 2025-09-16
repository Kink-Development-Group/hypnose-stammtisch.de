<?php

return [
  'app' => [
    'name' => 'Hypnose Stammtisch',
    'version' => '1.0.0',
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'base_url' => $_ENV['BASE_URL'] ?? '',
  ],

  'session' => [
    'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 86400),
    'name' => 'hypnose_session',
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
    'allow_private_ips' => filter_var($_ENV['ALLOW_PRIVATE_IPS'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'trusted_proxies' => array_filter(array_map('trim', explode(',', $_ENV['TRUSTED_PROXIES'] ?? ''))),
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
