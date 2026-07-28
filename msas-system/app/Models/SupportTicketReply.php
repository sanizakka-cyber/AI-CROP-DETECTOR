<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketReply extends Model
{
    protected $fillable = ['ticket_id', 'user_id', 'message', 'is_staff'];

    protected $casts = ['is_staff' => 'boolean'];

    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
    public function ticket(): BelongsTo { return $this->belongsTo(SupportTicket::class, 'ticket_id'); }
}