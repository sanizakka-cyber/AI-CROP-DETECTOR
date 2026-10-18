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
        'fallback_provider', 'fallback_voice', 'rate', 'safety_note', 'sort', 'audio_enabled',
    ];

    protected $casts = ['rate' => 'float', 'sort' => 'integer', 'audio_enabled' => 'boolean'];

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

    /**
     * Whether results are spoken in this language: audio switched on for it
     * (decision 2026-10-18: English and Hausa at launch; Yoruba and Igbo are
     * text only for now) and a voice provider that may be used. Without it
     * the translated text is still shown and Listen is hidden.
     */
    public function hasAudio(): bool
    {
        return (bool) $this->audio_enabled
            && $this->provider
            && array_key_exists($this->provider, \App\Services\Speech\Providers\ProviderRegistry::selectable());
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
            // Clients show Listen only when this is true.
            'hasVoice'    => $this->hasAudio(),
        ];
    }
}
