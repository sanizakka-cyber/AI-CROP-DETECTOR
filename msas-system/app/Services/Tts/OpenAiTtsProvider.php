<?php

namespace App\Services\Tts;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI TTS (POST https://api.openai.com/v1/audio/speech) — used only for
 * French, which Spitch doesn't cover. OpenAI's TTS voices are multilingual;
 * the spoken language follows the input text itself rather than a language
 * parameter, so $text must already be French.
 */
class OpenAiTtsProvider implements TtsProvider
{
    public function generate(string $text, string $voice): array
    {
        $apiKey = config('services.tts_openai.key');
        $model  = config('services.tts_openai.model', 'gpt-4o-mini-tts');

        if (!$apiKey) {
            throw new \RuntimeException('OPENAI_API_KEY (TTS) not configured');
        }

        $guzzle = new GuzzleClient(['timeout' => 30, 'http_errors' => false]);

        try {
            $resp = $guzzle->post('https://api.openai.com/v1/audio/speech', [
                'headers' => [
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'           => $model,
                    'input'           => $text,
                    'voice'           => $voice,
                    'response_format' => 'mp3',
                ],
            ]);
        } catch (\Throwable $e) {
            throw new \RuntimeException("OpenAI TTS request failed: {$e->getMessage()}", previous: $e);
        }

        $status = $resp->getStatusCode();
        if ($status < 200 || $status >= 300) {
            $body = substr((string) $resp->getBody(), 0, 300);
            Log::warning('OpenAI TTS non-200 response', ['status' => $status, 'body' => $body]);
            throw new \RuntimeException("OpenAI TTS error: HTTP {$status}");
        }

        $bytes = (string) $resp->getBody();
        if ($bytes === '') {
            throw new \RuntimeException('OpenAI TTS returned empty audio body');
        }

        return ['bytes' => $bytes, 'format' => 'mp3'];
    }
}
