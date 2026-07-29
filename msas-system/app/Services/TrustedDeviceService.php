<?php

namespace App\Services;

use App\Models\LoginHistory;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class TrustedDeviceService
{
    const COOKIE_NAME = 'msas_td';
    const TRUST_DAYS  = 30;

    public function isDeviceTrusted(Request $request, User $user): bool
    {
        $token = $request->cookie(self::COOKIE_NAME);
        if (! $token) {
            return false;
        }

        return TrustedDevice::where('user_id', $user->id)
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function trustDevice(Request $request, User $user): Cookie
    {
        $token = bin2hex(random_bytes(32));
        $ua    = $request->userAgent() ?? '';

        TrustedDevice::create([
            'user_id'      => $user->id,
            'token_hash'   => hash('sha256', $token),
            'device_name'  => LoginHistory::parseDevice($ua),
            'browser'      => LoginHistory::parseBrowser($ua),
            'platform'     => LoginHistory::parsePlatform($ua),
            'ip_address'   => $request->ip(),
            'last_used_at' => now(),
            'expires_at'   => now()->addDays(self::TRUST_DAYS),
        ]);

        return cookie(
            self::COOKIE_NAME,
            $token,
            self::TRUST_DAYS * 24 * 60,
            '/',
            null,
            config('session.secure', false),
            true,   // httpOnly
            false,  // raw
            'lax'   // sameSite
        );
    }

    public function refreshLastUsed(Request $request, User $user): void
    {
        $token = $request->cookie(self::COOKIE_NAME);
        if (! $token) {
            return;
        }

        TrustedDevice::where('user_id', $user->id)
            ->where('token_hash', hash('sha256', $token))
            ->update(['last_used_at' => now()]);
    }

    public function revokeAll(User $user): Cookie
    {
        TrustedDevice::where('user_id', $user->id)->delete();
        return \Cookie::forget(self::COOKIE_NAME);
    }
}
