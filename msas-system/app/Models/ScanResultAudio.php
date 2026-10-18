<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanResultAudio extends Model
{
    protected $table = 'scan_result_audio';

    protected $fillable = [
        'diagnosis_id', 'lang', 'voice', 'provider', 'rate', 'cache_key', 'audio_path', 'mime',
        'duration_sec', 'size_bytes', 'segments', 'status', 'attempts', 'latency_ms', 'error',
        'chunks', 'chunks_total', 'first_chunk_ms',
    ];

    protected $casts = [
        'rate'         => 'float',
        'duration_sec' => 'float',
        'size_bytes'   => 'integer',
        'segments'     => 'array',
        'chunks'       => 'array',
        'chunks_total' => 'integer',
        'first_chunk_ms' => 'integer',
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

    /** Sentence files finished so far, in reading order. */
    public function readyChunks(): array
    {
        $chunks = array_values(array_filter($this->chunks ?? [], fn ($c) => !empty($c['path'])));
        usort($chunks, fn ($a, $b) => $a['index'] <=> $b['index']);
        return $chunks;
    }
}
