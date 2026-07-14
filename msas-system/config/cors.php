<?php

$extraOrigins = array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', '')));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // In production set CORS_ALLOWED_ORIGINS=https://yourdomain.com in .env
    // Mobile apps send no Origin header, so '*' is safe for the API.
    'allowed_origins' => array_merge(
        ['*'],
        $extraOrigins,
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,
];
