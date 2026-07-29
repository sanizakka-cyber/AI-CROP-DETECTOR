<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id','ip_address','user_agent','device','browser','platform','country','success',
    ];

    protected $casts = [
        'success'    => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(int $userId, bool $success = true): void
    {
        $ua = request()->userAgent() ?? '';

        // Simple parse — no external library dependency
        $browser  = static::parseBrowser($ua);
        $platform = static::parsePlatform($ua);
        $device   = static::parseDevice($ua);

        static::create([
            'user_id'    => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr($ua, 0, 500),
            'browser'    => $browser,
            'platform'   => $platform,
            'device'     => $device,
            'success'    => $success,
        ]);
    }

    public static function parseBrowser(string $ua): string
    {
        if (str_contains($ua, 'Edg/'))    return 'Edge';
        if (str_contains($ua, 'Chrome'))  return 'Chrome';
        if (str_contains($ua, 'Firefox')) return 'Firefox';
        if (str_contains($ua, 'Safari'))  return 'Safari';
        if (str_contains($ua, 'Opera'))   return 'Opera';
        return 'Unknown';
    }

    public static function parsePlatform(string $ua): string
    {
        if (str_contains($ua, 'Windows')) return 'Windows';
        if (str_contains($ua, 'Mac'))     return 'macOS';
        if (str_contains($ua, 'Linux'))   return 'Linux';
        if (str_contains($ua, 'Android')) return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
        return 'Unknown';
    }

    public static function parseDevice(string $ua): string
    {
        if (str_contains($ua, 'Mobile') || str_contains($ua, 'Android') || str_contains($ua, 'iPhone')) return 'Mobile';
        if (str_contains($ua, 'iPad') || str_contains($ua, 'Tablet')) return 'Tablet';
        return 'Desktop';
    }
}
