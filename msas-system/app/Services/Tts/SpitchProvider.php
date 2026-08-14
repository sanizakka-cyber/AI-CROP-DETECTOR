<?php

namespace App\Services\Tts;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;

/**
 * Spitch (docs.spitch.app) — Yoruba/Hausa/Igbo/English TTS.
 *
 * IMPORTANT: the exact API base URL below is UNVERIFIED — Spitch's public
 * docs confirm the endpoint path (POST /v1/speech), auth pattern (Bearer
 * API key), and that the Python SDK's underlying REST call targets this
 * shape, but the live docs site returned 403 to automated fetches during
 * research, so the base host could not be directly confirmed. It's
 * configurable via SPITCH_BASE_URL specifically so this can be corrected
 * without a code change once real API access is available — check
 * https://docs.spitch.app/start/setup for the confirmed value and update
 * SPITCH_BASE_URL in the environment if it differs from the default below.
 */
class SpitchProvider implements TtsProvider
{
    public function generate(string $text, string $voice): array
    {
        $apiKey  = config('services.spitch.key');
        $baseUrl = rtrim(config('services.spitch.base_url', 'https://api.spitch.app'), '/');

        if (!$apiKey) {
            throw new \RuntimeException('SPITCH_API_KEY not configured');
        }

        $guzzle = new GuzzleClient(['timeout' => 30, 'http_errors' => false]);

        try {
            $resp = $guzzle->post("{$baseUrl}/v1/speech", [
                'headers' => [
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'audio/mpeg',
                ],
                'json' => [
                    'text'     => $text,
                    'voice'    => $voice,
                    'language' => $this->voiceLanguageHint($voice),
                    'format'   => 'mp3',
                ],
            ]);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Spitch request failed: {$e->getMessage()}", previous: $e);
        }

        $status = $resp->getStatusCode();
        if ($status < 200 || $status >= 300) {
            $body = substr((string) $resp->getBody(), 0, 300);
            Log::warning('Spitch TTS non-200 response', ['status' => $status, 'body' => $body]);
            throw new \RuntimeException("Spitch TTS error: HTTP {$status}");
        }

        $bytes = (string) $resp->getBody();
        if ($bytes === '') {
            throw new \RuntimeException('Spitch TTS returned empty audio body');
        }

        return ['bytes' => $bytes, 'format' => 'mp3'];
    }

    /** Best-effort language code for the request body, derived from the voice name via TtsLanguages. */
    private function voiceLanguageHint(string $voice): string
    {
        foreach (\App\Data\TtsLanguages::config() as $code => $cfg) {
            if (($cfg['provider'] ?? null) === 'spitch' && ($cfg['voice'] ?? null) === $voice) {
                return $code;
            }
        }
        return 'en';
    }
}
