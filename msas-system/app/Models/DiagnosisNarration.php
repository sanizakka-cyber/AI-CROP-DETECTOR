<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosisNarration extends Model
{
    protected $fillable = [
        'diagnosis_id',
        'language',
        'voice',
        'provider',
        'storage_path',
        'audio_format',
        'duration_seconds',
        'status',
        'error',
    ];

    public function diagnosis()
    {
        return $this->belongsTo(Diagnosis::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && !empty($this->storage_path);
    }
}
