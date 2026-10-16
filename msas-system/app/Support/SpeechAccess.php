<?php

namespace App\Support;

use App\Models\Diagnosis;
use App\Models\User;

/** Who may listen to, review and manage spoken results. */
class SpeechAccess
{
    public static function canReview(User $user): bool
    {
        return Roles::isAny($user->role, config('speech.reviewer_roles', []));
    }

    public static function canManage(User $user): bool
    {
        return Roles::isAny($user->role, config('speech.admin_roles', []));
    }

    /** The scan's owner, or a reviewer checking translations. */
    public static function canListen(User $user, Diagnosis $diagnosis): bool
    {
        return (int) $diagnosis->user_id === (int) $user->id || self::canReview($user);
    }
}
