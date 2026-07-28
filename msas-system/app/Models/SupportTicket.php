<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id', 'ticket_number', 'subject', 'message',
        'category', 'priority', 'status', 'assigned_to',
        'resolution', 'resolved_at',
    ];

    protected $casts = ['resolved_at' => 'datetime'];

    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function replies(): HasMany  { return $this->hasMany(SupportTicketReply::class, 'ticket_id')->oldest(); }

    public static function generateNumber(): string
    {
        return 'TKT-' . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    public function statusBadge(): string
    {
        return match($this->status) {
            'open'        => 'background:#dbeafe;color:#1d4ed8;',
            'in_progress' => 'background:#fef3c7;color:#b45309;',
            'resolved'    => 'background:#dcfce7;color:#166534;',
            'closed'      => 'background:#f1f5f9;color:#64748b;',
            default       => 'background:#f1f5f9;color:#64748b;',
        };
    }

    public function priorityBadge(): string
    {
        return match($this->priority) {
            'urgent' => 'background:#fef2f2;color:#dc2626;',
            'high'   => 'background:#fff7ed;color:#ea580c;',
            'normal' => 'background:#f0fdf4;color:#166534;',
            'low'    => 'background:#f8fafc;color:#94a3b8;',
            default  => 'background:#f8fafc;color:#94a3b8;',
        };
    }
}