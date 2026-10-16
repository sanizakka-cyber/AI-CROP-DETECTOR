<?php

namespace App\Models;

use App\Support\SpeechAccess;
use Illuminate\Database\Eloquent\Model;

/**
 * A language offered by "Listen to your result". Managed in admin, so adding
 * a language (e.g. Nigerian Pidgin) needs no code change.
 */
class SpeechLanguage extends Model
{
    public const STATUSES = ['draft', 'in_review', 'live', 'disabled'];

    protected $fillable = [
        'code', 'name_english', 'name_native', 'status', 'provider', 'voice',
        'fallback_provider', 'fallback_voice', 'rate', 'safety_note', 'sort',
    ];

    protected $casts = ['rate' => 'float', 'sort' => 'integer'];

    public static function findCode(?string $code): ?self
    {
        return $code ? static::where('code', $code)->first() : null;
    }

    /** Languages a user may pick: live ones, plus in-review ones for reviewers/admins. */
    public static function availableFor(?User $user)
    {
        $statuses = $user && SpeechAccess::canReview($user) ? ['live', 'in_review'] : ['live'];
        return static::whereIn('status', $statuses)->orderBy('sort')->orderBy('name_english')->get();
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function reviewedCount(): int
    {
        return ScanResultTranslation::where('lang', $this->code)->whereNotNull('reviewed_at')->count();
    }

    public function toPicker(): array
    {
        return [
            'code'        => $this->code,
            'name'        => $this->name_native,
            'nameEnglish' => $this->name_english,
            'status'      => $this->status,
            'voice'       => $this->voice,
            'hasVoice'    => (bool) $this->provider,
        ];
    }
}
