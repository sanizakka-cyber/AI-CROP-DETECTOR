<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken) {
            return $this->unauthenticated($request, 'No bearer token');
        }

        $hash  = hash('sha256', $bearerToken);
        $token = ApiToken::where('token_hash', $hash)->first();

        // Per-device token (api_tokens). Tokens issued before that table
        // existed live only in users.api_token and keep working.
        $user = $token
            ? User::where('id', $token->user_id)->where('is_active', true)->first()
            : User::where('api_token', $hash)->where('is_active', true)->first();

        if (! $user) {
            return $this->unauthenticated($request, 'Unknown, revoked or inactive-account token');
        }

        if ($token && (! $token->last_used_at || $token->last_used_at->lt(now()->subMinutes(5)))) {
            $token->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        $user->update(['last_seen' => now()]);
        Auth::setUser($user);
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }

    /** A scan gets the coded SESSION_EXPIRED answer (with a reference); other routes keep the plain 401. */
    private function unauthenticated(Request $request, string $why): Response
    {
        if (\App\Services\Scan\ScanErrorReporter::isScanRequest($request)) {
            return app(\App\Services\Scan\ScanErrorReporter::class)->respond($request,
                new \App\Support\Scan\ScanFailure(\App\Support\Scan\ScanErrorCode::SESSION_EXPIRED, 401, $why));
        }
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
