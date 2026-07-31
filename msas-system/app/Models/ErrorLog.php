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
                'message'         => mb_substr($e->getMessage(), 0, 500),
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
}
