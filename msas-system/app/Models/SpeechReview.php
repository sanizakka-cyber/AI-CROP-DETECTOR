<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpeechReview extends Model
{
    /** Error types a reviewer can mark in the text. dosage and chemical block go-live. */
    public const ERROR_TYPES = [
        'dosage'        => 'Wrong dose, amount or unit',
        'chemical'      => 'Wrong chemical, medicine or product',
        'meaning'       => 'Meaning changed or unclear',
        'grammar'       => 'Grammar or spelling',
        'pronunciation' => 'Mispronounced in the audio',
        'other'         => 'Other',
    ];
    public const BLOCKING = ['dosage', 'chemical'];

    protected $fillable = [
        'item_id', 'user_id', 'reviewer_kind', 'accuracy', 'voice_ratings',
        'preferred_label', 'preferred_candidate', 'errors', 'dosage_errors', 'comment',
    ];

    protected $casts = [
        'errors'        => 'array',
        'accuracy'      => 'integer',
        'voice_ratings' => 'array',
        'dosage_errors' => 'integer',
        'preferred_candidate' => 'integer',
    ];

    /** Ratings given to the voice behind candidate index $candidate, or null. */
    public function ratingsForCandidate(int $candidate): ?array
    {
        foreach ($this->voice_ratings ?? [] as $label => $r) {
            if (($this->item->label_map[$label] ?? null) === $candidate) {
                return $r;
            }
        }
        return null;
    }

    public function item()
    {
        return $this->belongsTo(SpeechReviewItem::class, 'item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
