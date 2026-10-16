<?php

namespace App\Services\Speech\Providers;

use Illuminate\Support\Facades\Http;

/**
 * Optional cloud fallback (POST https://api.elevenlabs.io/v1/text-to-speech/{voice_id}).
 * `voice` is an ElevenLabs voice id. Use only for languages the chosen model
 * supports, set per language in admin. Pauses are written as line breaks,
 * since the API returns one file without per-segment times.
 */
class ElevenLabsProvider implements TtsProvider
{
    public function synthesize(array $segments, string $lang, string $voice, float $rate): SynthesizedAudio
    {
        $cfg = config('speech.elevenlabs');
        if (empty($cfg['key'])) {
            throw TtsException::permanent('ELEVENLABS_API_KEY is not configured.');
        }

        $body = [
            'text'           => implode("\n", array_column($segments, 'text')),
            'voice_settings' => ['speed' => max(0.7, min(1.2, $rate))],
        ];
        if (!empty($cfg['model'])) {
            $body['model_id'] = $cfg['model'];
        }

        try {
            $res = Http::timeout(90)
                ->withHeaders(['xi-api-key' => $cfg['key'], 'Accept' => 'audio/mpeg'])
                ->post('https://api.elevenlabs.io/v1/text-to-speech/' . rawurlencode($voice)
                    . '?output_format=' . urlencode($cfg['output_format']) . '&enable_logging=false', $body);
        } catch (\Throwable $e) {
            throw new TtsException("ElevenLabs unreachable: {$e->getMessage()}", previous: $e);
        }

        if (in_array($res->status(), [400, 401, 403, 404, 422], true)) {
            throw TtsException::permanent("ElevenLabs rejected the request: HTTP {$res->status()}");
        }
        if (!$res->successful() || $res->body() === '') {
            throw new TtsException("ElevenLabs error: HTTP {$res->status()}");
        }

        // Duration from the bitrate in the output format name, e.g. mp3_22050_32 -> 32 kbps.
        $kbps = (int) (explode('_', $cfg['output_format'])[2] ?? 32) ?: 32;
        $duration = round(strlen($res->body()) * 8 / ($kbps * 1000), 2);

        return new SynthesizedAudio($res->body(), 'audio/mpeg', 'mp3', $duration, SynthesizedAudio::estimateTimes($segments, $duration));
    }
}
