<?php

$extraOrigins = array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', '')));

// In production, require CORS_ALLOWED_ORIGINS to be set explicitly.
// Falling back to wildcard in production would allow any website to make
// authenticated API requests using a visitor's stored bearer token.
// Mobile app requests carry no Origin header and are unaffected by CORS.
//
// Config files are require()'d directly by Laravel's LoadConfiguration
// bootstrapper very early in boot -- before the container is guaranteed
// to have every binding (e.g. 'env') wired up in every code path. This
// used to call app()->environment('production'), a container call, which
// surfaced as "Target class [env] does not exist" specifically when
// running php artisan test (masked for a while by an unrelated bug in the
// exception reporter that crashed trying to report this). Config files
// must only ever depend on real environment variables, never on app().
$allowedOrigins = !empty($extraOrigins)
    ? $extraOrigins
    : (env('APP_ENV') === 'production' ? [] : ['*']);

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
