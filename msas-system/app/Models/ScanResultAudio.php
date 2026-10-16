<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanResultAudio extends Model
{
    protected $table = 'scan_result_audio';

    protected $fillable = [
        'diagnosis_id', 'lang', 'voice', 'provider', 'rate', 'cache_key', 'audio_path', 'mime',
        'duration_sec', 'size_bytes', 'segments', 'status', 'attempts', 'latency_ms', 'error',
    ];

    protected $casts = [
        'rate'         => 'float',
        'duration_sec' => 'float',
        'size_bytes'   => 'integer',
        'segments'     => 'array',
        'attempts'     => 'integer',
    ];

    public function diagnosis()
    {
        return $this->belongsTo(Diagnosis::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && !empty($this->audio_path);
    }
}
