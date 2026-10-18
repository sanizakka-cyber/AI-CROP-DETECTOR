<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A user assigned to review one language: a native speaker, or an extension officer / vet (expert). */
class SpeechReviewer extends Model
{
    public const KINDS = ['native' => 'Native speaker', 'expert' => 'Extension officer or vet'];

    protected $fillable = ['user_id', 'lang', 'kind', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Languages this user reviews, as [lang => kind]. */
    public static function languagesFor(?User $user): array
    {
        if (!$user) {
            return [];
        }
        return static::where('user_id', $user->id)->where('active', true)->pluck('kind', 'lang')->all();
    }
}
