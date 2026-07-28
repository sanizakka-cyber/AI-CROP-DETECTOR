<?php

$extraOrigins = array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', '')));

// In production, require CORS_ALLOWED_ORIGINS to be set explicitly.
// Falling back to wildcard in production would allow any website to make
// authenticated API requests using a visitor's stored bearer token.
// Mobile app requests carry no Origin header and are unaffected by CORS.
$allowedOrigins = !empty($extraOrigins)
    ? $extraOrigins
    : (app()->environment('production') ? [] : ['*']);

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,
];
