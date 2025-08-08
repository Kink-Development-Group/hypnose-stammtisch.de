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
