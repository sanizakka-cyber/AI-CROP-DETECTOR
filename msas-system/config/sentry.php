<?php

return [

    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    'environment' => env('APP_ENV', 'production'),

    'release' => env('APP_VERSION', '1.0.0'),

    'breadcrumbs' => [
        'logs'                 => true,
        'cache'                => true,
        'livewire'             => true,
        'sql_queries'          => true,
        'sql_bindings'         => false,  // never log query bindings (may contain PII)
        'queue_info'           => true,
        'command_info'         => true,
        'http_client_requests' => true,
    ],

    'tracing' => [
        'queue_job_transactions'   => true,
        'queue_jobs'               => true,
        'sql_queries'              => true,
        'sql_origin'               => true,
        'views'                    => true,
        'livewire'                 => true,
        'http_client_requests'     => true,
        'redis_commands'           => false,
        'redis_origin'             => false,
        'requests'                 => true,
        'missing_routes'           => true,
        'schedule'                 => true,
    ],

    'traces_sample_rate'   => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),

    // Only report these HTTP status codes (exclude expected errors like 404/422)
    'ignore_exceptions' => [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Validation\ValidationException::class,
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
    ],

    'ignore_transactions' => [
        '/up',          // Render.com health-check endpoint
        '/ping',
    ],

    'send_default_pii' => false,  // never send PII to Sentry without explicit user consent
];
