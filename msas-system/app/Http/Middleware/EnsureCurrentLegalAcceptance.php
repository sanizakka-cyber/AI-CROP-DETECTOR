<?php

namespace App\Http\Middleware;

use App\Services\LegalConsentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signed-in web users who have not accepted the current Terms of Service and
 * Privacy Policy versions (existing accounts from before consent was
 * recorded, or after a version bump in config/legal.php) are sent to a page
 * where they accept them once before continuing.
 *
 * Only full-page GET navigations are redirected, so forms already in
 * progress, AJAX/JSON calls, payment callbacks and webhooks are never broken.
 */
class EnsureCurrentLegalAcceptance
{
    private const ALLOWED_ROUTES = [
        'legal.accept.show', 'legal.accept.store',
        'legal.privacy', 'legal.terms', 'legal.refund', 'legal.faq', 'legal.help', 'legal.cookie',
        'logout', 'otp.verify', 'otp.verify.post', 'otp.resend', 'verification.link',
        'password.change', 'password.change.update',
        'subscription.paystack.callback',
    ];

    public function __construct(private LegalConsentService $legal) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user
            || ! config('legal.enforce_reacceptance', true)
            || ! $request->isMethod('GET')
            || $request->expectsJson()
            || $request->is('api/*', 'webhooks/*')
            || in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true)
            || ! $this->legal->needsReacceptance($user)) {
            return $next($request);
        }

        $request->session()->put('url.intended', $request->fullUrl());

        return redirect()->route('legal.accept.show');
    }
}
