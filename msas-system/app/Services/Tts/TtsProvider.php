<?php

namespace App\Services\Tts;

interface TtsProvider
{
    /**
     * Generate speech audio for the given text/voice.
     *
     * @return array{bytes: string, format: string}
     * @throws \RuntimeException on any failure — callers must catch and fall
     *         back to browser TTS rather than let this propagate to the user.
     */
    public function generate(string $text, string $voice): array;
}
