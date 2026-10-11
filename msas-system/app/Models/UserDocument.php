<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDocument extends Model
{
    public const STATUS_PENDING_REVIEW        = 'pending_review';
    public const STATUS_APPROVED              = 'approved';
    public const STATUS_REJECTED              = 'rejected';
    public const STATUS_RESUBMISSION_REQUIRED = 'resubmission_required';

    public const STATUSES = [
        self::STATUS_PENDING_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_RESUBMISSION_REQUIRED,
    ];

    /**
     * Explicit allowed transitions, enforced server-side (not just hidden
     * via disabled frontend buttons) wherever a status change is applied.
     * Both 'rejected' and 'resubmission_required' accept a replacement
     * upload -- they differ only in how serious/final the admin's decision
     * reads to the applicant, not in what happens next.
     */
    public const ALLOWED_TRANSITIONS = [
        self::STATUS_PENDING_REVIEW        => [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_RESUBMISSION_REQUIRED],
        self::STATUS_REJECTED              => [self::STATUS_PENDING_REVIEW],
        self::STATUS_RESUBMISSION_REQUIRED => [self::STATUS_PENDING_REVIEW],
        self::STATUS_APPROVED              => [],
    ];

    protected $fillable = [
        'user_id', 'document_type', 'document_label',
        'original_name', 'mime_type', 'file_size', 'content_base64',
        'status', 'reviewed_at', 'reviewed_by', 'review_reason', 'replaces_document_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** The earlier document this one was uploaded to replace, if any. */
    public function replaces(): BelongsTo
    {
        return $this->belongsTo(UserDocument::class, 'replaces_document_id');
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::ALLOWED_TRANSITIONS[$this->status] ?? [], true);
    }

    public function isAwaitingResubmission(): bool
    {
        return in_array($this->status, [self::STATUS_REJECTED, self::STATUS_RESUBMISSION_REQUIRED], true);
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    public function dataUri(): string
    {
        return 'data:' . $this->mime_type . ';base64,' . $this->content_base64;
    }
}
