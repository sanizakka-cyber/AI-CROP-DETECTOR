<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'         => \App\Http\Middleware\RoleMiddleware::class,
            'permission'   => \App\Http\Middleware\PermissionMiddleware::class,
            'auth.api'     => \App\Http\Middleware\ApiAuthenticate::class,
            'subscription' => \App\Http\Middleware\RequireSubscription::class,
        ]);

        // Restore user's chosen language from session on every web request
        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);

        // Exclude payment webhooks from CSRF (they're verified by HMAC signature instead)
        $middleware->validateCsrfTokens(except: [
            'webhooks/paystack',
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
