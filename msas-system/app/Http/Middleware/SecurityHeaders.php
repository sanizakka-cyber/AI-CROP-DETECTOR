<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Content-Security-Policy: blocks injected scripts from untrusted origins,
        // prevents clickjacking (redundant with X-Frame-Options but defense-in-depth),
        // and stops base-tag / form-action phishing even with inline scripts allowed.
        // 'unsafe-eval' is required because Alpine.js evaluates directive expressions
        // (x-show, @click, x-data, ...) as live JS strings at runtime — without it every
        // Alpine directive on every page throws and silently fails to bind.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.paystack.co https://meet.jit.si https://browser.sentry-cdn.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://cdnjs.cloudflare.com",
            "img-src 'self' data: https://ui-avatars.com https://images.unsplash.com",
            "font-src 'self' data: https://fonts.bunny.net https://cdnjs.cloudflare.com",
            "connect-src 'self' https://api.open-meteo.com https://api.paystack.co https://*.ingest.sentry.io https://*.ingest.de.sentry.io https://*.ingest.us.sentry.io",
            "frame-src 'self' https://meet.jit.si",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        // Only send HSTS over HTTPS connections
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
