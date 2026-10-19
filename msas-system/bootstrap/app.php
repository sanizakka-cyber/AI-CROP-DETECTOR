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
            'heic'                => \App\Http\Middleware\ConvertHeicUploads::class,
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

        // Phase 9 §A2/A5: a value that's all digits (e.g. a very large
        // number) still matches the [0-9]+ route constraints in
        // AppServiceProvider -- it reaches the database, where it can
        // overflow Postgres's bigint range (SQLSTATE 22003, "numeric
        // value out of range"), not just arrive as a non-numeric string
        // (22P02, "invalid input syntax for type bigint"). Both are the
        // same class of problem -- malformed/out-of-range client input,
        // not an application bug -- and get the same clean-404 treatment
        // in both closures below.
        $idShapedSqlstates = ['22P02', '22003'];

        // Structured logging for all unhandled exceptions.
        //
        // This closure can run before Laravel's facade root or container
        // bindings (request, auth, even Log itself) are guaranteed to be
        // set — e.g. an exception thrown while service providers are still
        // registering. A reporter's one job is to never itself throw and
        // mask the exception it was asked to report, so everything
        // Laravel-dependent below is wrapped, with a raw error_log() as
        // the last-resort fallback that needs nothing from the framework.
        $exceptions->report(function (\Throwable $e) use ($idShapedSqlstates) {
          try {
            // See $idShapedSqlstates comment above. Route::pattern()
            // constraints (AppServiceProvider::constrainIdRouteParameters())
            // stop a non-numeric value before it reaches the database for
            // every known {id}-style route, but this is a second,
            // independent layer for anything that reaches a numeric
            // column some other way (e.g. an id taken from the request
            // body rather than the URL, which a route pattern can't
            // constrain) or that's all-digits but out of bigint range.
            // This is bad client input, not an application bug -- it must
            // render as a clean 404 (handled in the render() block below)
            // and must never be written to ErrorLog as an unresolved error.
            if ($e instanceof \Illuminate\Database\QueryException
                && in_array($e->errorInfo[0] ?? $e->getCode(), $idShapedSqlstates, true)) {
                return;
            }

            $context = [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'url'       => null,
                'method'    => null,
                'user_id'   => null,
                'user_role' => null,
                'ip'        => null,
            ];
            try {
                if (app()->bound('request')) {
                    $context['url']    = request()->fullUrl();
                    $context['method'] = request()->method();
                    $context['ip']     = request()->ip();
                }
                if (app()->bound('auth')) {
                    $context['user_id']   = auth()->id();
                    $context['user_role'] = auth()->user()?->role;
                }
            } catch (\Throwable) {
                // Context gathering must never prevent the exception below
                // from being logged.
            }

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
          } catch (\Throwable $reporterFailure) {
            // Never let a problem in the reporter itself swallow the
            // original exception without a trace.
            error_log(sprintf(
                "[app.php reporter failed] %s: %s in %s:%d\n[ORIGINAL EXCEPTION] %s: %s in %s:%d\n%s",
                get_class($reporterFailure), $reporterFailure->getMessage(),
                $reporterFailure->getFile(), $reporterFailure->getLine(),
                get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(),
                $e->getTraceAsString(),
            ));
          }
        });

        // Classified scan failures are recorded in scan_errors by the reporter;
        // unexpected exceptions (INTERNAL_ERROR) are still reported as usual.
        $exceptions->dontReport(\App\Exceptions\ScanFailedException::class);

        // Scan requests (web form and app): every failure gets a code, a
        // reference and a role-aware answer (App\Services\Scan). Registered
        // first so it wins over the generic JSON answers below for these routes only.
        $exceptions->render(function (\Throwable $e, $request) {
            try {
                if (! \App\Services\Scan\ScanErrorReporter::isScanRequest($request)) {
                    return null;
                }
                $failure = app(\App\Services\Scan\ScanFailureClassifier::class)->forRequestException($e, $request);
                return $failure ? app(\App\Services\Scan\ScanErrorReporter::class)->respond($request, $failure) : null;
            } catch (\Throwable $reporting) {
                error_log('[scan error reporting failed] ' . $reporting->getMessage());
                return null; // fall back to Laravel's own answer rather than mask the original error
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

        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) use ($idShapedSqlstates) {
            if (! in_array($e->errorInfo[0] ?? $e->getCode(), $idShapedSqlstates, true)) {
                return null; // not this specific case -- fall through to Laravel's default 500 handling
            }
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => 'Resource not found.'], 404);
            }
            // No app-level resources/views/errors/404.blade.php exists, so
            // response()->view('errors.404', ...) would itself crash with
            // a missing-view error -- a plain response is the only option
            // here that can't fail the same way a view lookup could.
            return response('Not Found', 404);
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => 'Resource not found.'], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Laravel's own prepareException() converts a
                // ModelNotFoundException into this exact exception type
                // (wrapping the original as getPrevious()) before any
                // registered renderer ever sees it — so the dedicated
                // ModelNotFoundException handler above never actually
                // fires. Recover the distinction here instead: a
                // findOrFail() miss should read "record not found", not
                // "route not found".
                if ($e->getPrevious() instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return response()->json(['error' => 'Resource not found.'], 404);
                }
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
