<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    protected $fillable = [
        'category', 'level', 'exception_class', 'message',
        'file', 'line', 'url', 'method',
        'user_id', 'user_role', 'ip_address',
        'resolved', 'resolved_at',
    ];

    protected $casts = [
        'resolved'    => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function capture(\Throwable $e, string $category = 'app'): void
    {
        try {
            static::create([
                'category'        => $category,
                'level'           => 'error',
                'exception_class' => get_class($e),
                'message'         => mb_substr(self::redactConnectionDetails($e->getMessage()), 0, 500),
                'file'            => mb_substr($e->getFile(), 0, 500),
                'line'            => $e->getLine(),
                'url'             => mb_substr(request()->fullUrl(), 0, 1000),
                'method'          => request()->method(),
                'user_id'         => auth()->id(),
                'user_role'       => auth()->user()?->role,
                'ip_address'      => request()->ip(),
            ]);
        } catch (\Throwable) {
            // Never let error logging crash the app
        }
    }

    /**
     * Phase 9 §A3: a raw PDO/Postgres connection error's message can
     * include the database host (and sometimes the configured
     * username) verbatim -- this page is readable by ceo and admin, not
     * only ceo, but neither role has a reason to see where the database
     * physically lives. Strips the exact configured host/username out
     * of any captured message before it's stored, rather than trying to
     * pattern-match every possible driver error format.
     */
    private static function redactConnectionDetails(string $message): string
    {
        $host = config('database.connections.pgsql.host');
        $user = config('database.connections.pgsql.username');

        if ($host) {
            $message = str_replace($host, '[db-host-redacted]', $message);
        }
        if ($user) {
            $message = str_replace($user, '[db-user-redacted]', $message);
        }

        return $message;
    }
}
