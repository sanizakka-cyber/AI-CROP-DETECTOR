<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpeechReviewItem extends Model
{
    public const KINDS = [
        'scan'        => 'Scan result',
        'numbers'     => 'Numbers and units',
        'labels'      => 'Screen labels',
        'safety_note' => 'Spoken safety note',
        'lexicon'     => 'Pronunciations',
    ];

    public const SCAN_TYPES = ['crop' => 'Plant / Crop', 'livestock' => 'Livestock', 'soil' => 'Soil Sample', 'pest' => 'Pest ID'];

    protected $fillable = [
        'pack_id', 'position', 'kind', 'scan_type', 'diagnosis_id', 'source_lang',
        'source_json', 'translation_json', 'label_map', 'audio',
    ];

    protected $casts = [
        'source_json'      => 'array',
        'translation_json' => 'array',
        'label_map'        => 'array',
        'audio'            => 'array',
    ];

    public function pack()
    {
        return $this->belongsTo(SpeechReviewPack::class, 'pack_id');
    }

    public function reviews()
    {
        return $this->hasMany(SpeechReview::class, 'item_id');
    }

    /** The candidate voice behind a label (admin only; reviewers never see it). */
    public function candidateFor(string $label): ?array
    {
        $i = $this->label_map[$label] ?? null;
        return $i === null ? null : ($this->pack->candidates[$i] ?? null) + ['index' => $i];
    }

    /** Labels with a playable file, in A, B, C order. */
    public function playableLabels(): array
    {
        $labels = array_keys(array_filter($this->audio ?? [], fn ($a) => !empty($a['path'])));
        sort($labels);
        return $labels;
    }
}
