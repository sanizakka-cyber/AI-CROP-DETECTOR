<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanResultTranslation extends Model
{
    protected $fillable = [
        'diagnosis_id', 'lang', 'source_lang', 'source_hash', 'source_json', 'translated_json',
        'status', 'check_problems', 'model', 'error', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'source_json'     => 'array',
        'translated_json' => 'array',
        'check_problems'  => 'array',
        'reviewed_at'     => 'datetime',
    ];

    public function diagnosis()
    {
        return $this->belongsTo(Diagnosis::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && is_array($this->translated_json);
    }
}
