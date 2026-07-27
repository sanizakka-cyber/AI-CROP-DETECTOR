<?php

namespace App\Http\Middleware;

use App\Http\Controllers\TwoFactorController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session('2fa_user_id')) {
            // User is mid-2FA — only allow the 2FA routes through
            if (!$request->routeIs('2fa.*')) {
                return redirect()->route('2fa.verify');
            }
        }
        return $next($request);
    }
}
