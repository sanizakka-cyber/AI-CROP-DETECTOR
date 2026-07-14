<?php

namespace App\Http\Middleware;

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

        $user = User::where('api_token', hash('sha256', $bearerToken))
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->update(['last_seen' => now()]);
        Auth::setUser($user);
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
