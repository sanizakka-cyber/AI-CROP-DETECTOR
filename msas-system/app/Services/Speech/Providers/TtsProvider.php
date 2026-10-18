<?php

namespace App\Services\Speech\Providers;

/**
 * One interface for every voice provider (self-hosted YarnGPT2 / Farmerline,
 * or a cloud provider). The text arrives already translated, normalised and
 * split into short segments, each with the pause that follows it.
 */
interface TtsProvider
{
    /**
     * @param array<int, array{text: string, pause_ms: int}> $segments
     * @throws TtsException on any failure (the caller retries and shows the text meanwhile)
     */
    public function synthesize(array $segments, string $lang, string $voice, float $rate): SynthesizedAudio;
}
