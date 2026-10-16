<?php

namespace App\Services\Speech\Providers;

class SynthesizedAudio
{
    /**
     * @param array<int, array{0: float, 1: float}> $segmentTimes start/end seconds per input segment
     */
    public function __construct(
        public string $bytes,
        public string $mime,
        public string $extension,
        public float $durationSec,
        public array $segmentTimes,
    ) {
    }

    /** Proportional timings when a provider returns one file without per-segment times. */
    public static function estimateTimes(array $segments, float $duration): array
    {
        $weights = array_map(fn ($s) => mb_strlen($s['text']) * 60 + ($s['pause_ms'] ?? 0), $segments);
        $total = max(1, array_sum($weights));
        $t = 0.0;
        $times = [];
        foreach ($segments as $i => $s) {
            $speech = $duration * (mb_strlen($s['text']) * 60) / $total;
            $times[] = [round($t, 2), round($t + $speech, 2)];
            $t += $duration * $weights[$i] / $total;
        }
        return $times;
    }
}
