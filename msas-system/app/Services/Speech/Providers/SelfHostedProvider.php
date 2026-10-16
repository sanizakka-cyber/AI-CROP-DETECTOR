<?php

namespace App\Services\Speech\Providers;

use Illuminate\Support\Facades\Http;

/**
 * The MSAS speech service (tts-service/, FastAPI in Docker) running YarnGPT2
 * or the Farmerline Hausa model.
 */
class SelfHostedProvider implements TtsProvider
{
    public function __construct(private string $engine)
    {
    }

    public function synthesize(array $segments, string $lang, string $voice, float $rate): SynthesizedAudio
    {
        $cfg = config('speech.self_hosted');
        try {
            $http = Http::timeout($cfg['timeout'])->connectTimeout(15)->acceptJson();
            if (!empty($cfg['key'])) {
                $http = $http->withToken($cfg['key']);
            }
            $res = $http->post(rtrim($cfg['url'], '/') . '/synthesize', [
                'engine'       => $this->engine,
                'lang'         => $lang,
                'voice'        => $voice,
                'rate'         => $rate,
                'format'       => config('speech.audio_format', 'mp3'),
                'bitrate_kbps' => (int) config('speech.bitrate_kbps', 40),
                'segments'     => array_map(fn ($s) => ['text' => $s['text'], 'pause_ms' => (int) $s['pause_ms']], $segments),
            ]);
        } catch (\Throwable $e) {
            throw new TtsException("Speech service unreachable: {$e->getMessage()}", previous: $e);
        }

        if ($res->status() === 422) {
            throw TtsException::permanent('Speech service rejected the request: ' . json_encode($res->json('detail')));
        }
        if (!$res->successful()) {
            throw new TtsException("Speech service error: HTTP {$res->status()}");
        }

        $bytes = base64_decode((string) $res->json('audio_base64'), true);
        if ($bytes === false || $bytes === '') {
            throw new TtsException('Speech service returned no audio.');
        }

        $times = array_map(fn ($s) => [(float) $s['start'], (float) $s['end']], $res->json('segments') ?? []);
        if (count($times) !== count($segments)) {
            $times = SynthesizedAudio::estimateTimes($segments, (float) $res->json('duration_sec'));
        }

        return new SynthesizedAudio(
            $bytes,
            (string) ($res->json('mime') ?: 'audio/mpeg'),
            $res->json('format') === 'opus' ? 'ogg' : 'mp3',
            (float) $res->json('duration_sec'),
            $times,
        );
    }
}
