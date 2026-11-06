<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recorded agreement: a user accepting a version of the Terms, Privacy
 * Policy or Refund Policy (or declaring their age) in a given context.
 * Rows are written only by LegalConsentService and never edited.
 */
class LegalAcceptance extends Model
{
    protected $fillable = [
        'user_id', 'document', 'version', 'context', 'reference',
        'ip_address', 'user_agent', 'app_version', 'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
