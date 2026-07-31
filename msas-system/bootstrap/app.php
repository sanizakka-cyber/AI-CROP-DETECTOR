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
        // Trust Render's HTTPS termination proxy so $request->isSecure() returns true,
        // HSTS gets sent, and client IPs are read from X-Forwarded-For (not the proxy IP).
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role'                => \App\Http\Middleware\RoleMiddleware::class,
            'permission'          => \App\Http\Middleware\PermissionMiddleware::class,
            'auth.api'            => \App\Http\Middleware\ApiAuthenticate::class,
            'subscription'        => \App\Http\Middleware\RequireSubscription::class,
            'force.password.reset'=> \App\Http\Middleware\ForcePasswordReset::class,
            '2fa'                 => \App\Http\Middleware\RequireTwoFactor::class,
        ]);

        // Add security headers to all web responses
        $middleware->appendToGroup('web', \App\Http\Middleware\SecurityHeaders::class);

        // Restore user's chosen language from session on every web request
        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);

        // Intercept mid-2FA sessions so unauthenticated users can't bypass OTP
        $middleware->appendToGroup('web', \App\Http\Middleware\RequireTwoFactor::class);

        // Redirect users with a temporary password to the change-password page
        $middleware->appendToGroup('web', \App\Http\Middleware\ForcePasswordReset::class);

        // Exclude payment webhooks from CSRF (they're verified by HMAC signature instead)
        $middleware->validateCsrfTokens(except: [
            'webhooks/paystack',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Structured logging for all unhandled exceptions
        $exceptions->report(function (\Throwable $e) {
            $context = [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'url'       => request()->fullUrl(),
                'method'    => request()->method(),
                'user_id'   => auth()->id(),
                'user_role' => auth()->user()?->role,
                'ip'        => request()->ip(),
            ];

            // Categorise for easier log filtering
            $category = match(true) {
                $e instanceof \Illuminate\Database\QueryException              => 'database',
                $e instanceof \Illuminate\Auth\AuthenticationException         => 'auth',
                $e instanceof \Illuminate\Auth\Access\AuthorizationException   => 'auth',
                $e instanceof \App\Exceptions\PaymentException ?? false        => 'payment',
                str_contains($e->getMessage(), 'OTP')                          => 'otp',
                str_contains($e->getMessage(), 'SMS')                          => 'sms',
                str_contains($e->getMessage(), 'mail')                         => 'email',
                str_contains(strtolower($e->getMessage()), 'upload')           => 'file_upload',
                str_contains(strtolower($e->getMessage()), 'marketplace')      => 'marketplace',
                str_contains(strtolower($e->getMessage()), 'paystack')         => 'payment',
                str_contains(strtolower($e->getMessage()), 'ai engine')        => 'ai',
                default                                                         => 'app',
            };

            \Illuminate\Support\Facades\Log::error("[{$category}] Unhandled exception", $context);

            // Persist to DB for the monitoring dashboard (non-blocking)
            \App\Models\ErrorLog::capture($e, $category);

            // Forward to Sentry for real-time alerting (active after: composer require sentry/sentry-laravel)
            if (class_exists(\Sentry\Laravel\Integration::class) && config('sentry.dsn')) {
                \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($e, $context): void {
                    $scope->setTag('category', $context['category'] ?? 'app');
                    $scope->setTag('role', $context['user_role'] ?? 'guest');
                    $scope->setContext('request', [
                        'url'    => $context['url'],
                        'method' => $context['method'],
                        'ip'     => $context['ip'],
                    ]);
                    if ($context['user_id']) {
                        \Sentry\configureScope(fn ($s) => $s->setUser(['id' => $context['user_id']]));
                    }
                    \Sentry\captureException($e);
                });
            }
        });

        // Return standard JSON for all API errors (request path starts with /api)
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error'   => 'Validation failed.',
                    'details' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => 'Resource not found.'], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => 'Endpoint not found.'], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => 'Method not allowed.'], 405);
            }
        });

        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => 'Too many requests. Please slow down.'], 429);
            }
        });

        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status  = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                $message = app()->isProduction() ? 'An unexpected error occurred.' : $e->getMessage();
                return response()->json(['error' => $message], $status);
            }
        });
    })->create();
