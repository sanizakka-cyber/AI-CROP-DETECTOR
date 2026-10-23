<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A labelled photo for the evaluation set (Phase 12.2). Provenance is part of the record. */
class TrainingImage extends Model
{
    protected $fillable = [
        'mode', 'species', 'condition_label', 'synonyms', 'expected_outcome', 'label_basis', 'body_part',
        'state', 'lga', 'captured_on', 'source_type', 'source_name', 'source_url', 'licence',
        'consent_reference', 'consent_confirmed',
    ];

    protected $casts = [
        'captured_on'       => 'date',
        'consent_confirmed' => 'boolean',
        'reviewed_at'       => 'datetime',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Good enough to measure accuracy against: approved, and the label confirmed by a lab or a vet. */
    public function scopeEvalReady($q)
    {
        return $q->where('review_status', 'approved')->whereIn('label_basis', ['lab_confirmed', 'vet_diagnosis']);
    }
}
