<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'user_id', 'type', 'rating', 'message', 'page', 'status', 'admin_notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return match($this->type) {
            'bug'     => 'Bug Report',
            'feature' => 'Feature Request',
            'praise'  => 'Praise',
            default   => 'General Feedback',
        };
    }
}