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
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $hash  = hash('sha256', $bearerToken);
        $token = ApiToken::where('token_hash', $hash)->first();

        // Per-device token (api_tokens). Tokens issued before that table
        // existed live only in users.api_token and keep working.
        $user = $token
            ? User::where('id', $token->user_id)->where('is_active', true)->first()
            : User::where('api_token', $hash)->where('is_active', true)->first();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($token && (! $token->last_used_at || $token->last_used_at->lt(now()->subMinutes(5)))) {
            $token->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        $user->update(['last_seen' => now()]);
        Auth::setUser($user);
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
