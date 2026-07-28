<?php

$extraOrigins = array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', '')));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Set CORS_ALLOWED_ORIGINS=https://yourdomain.com in .env for production.
    // Mobile apps send no Origin header, so they are unaffected by CORS policy.
    // Falls back to '*' only when no explicit origins are configured (local/dev).
    'allowed_origins' => !empty($extraOrigins) ? $extraOrigins : ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,
];
