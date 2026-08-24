<?php

namespace App\Http\Controllers;

use App\Services\AiResponseNormalizer;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiWidgetController extends Controller
{
    private function client(): GuzzleClient
    {
        return new GuzzleClient([
            'connect_timeout' => 30,
            'timeout'         => 90,
            'http_errors'     => false,
        ]);
    }

    private function baseUrl(): string
    {
        return rtrim(config('services.ai_engine.url', ''), '/');
    }

    private function headers(): array
    {
        $key = config('services.ai_engine.key', '');
        return $key ? ['Authorization' => "Bearer {$key}"] : [];
    }

    public function weather(Request $request): JsonResponse
    {
        $request->validate([
            'location' => 'nullable|string|max:200',
            'crop'     => 'nullable|string|max:100',
            'language' => 'nullable|string|max:10',
        ]);

        $base = $this->baseUrl();
        if (!$base) {
            return response()->json(['error' => 'AI engine not configured'], 503);
        }

        try {
            $boundary = '----MSASWeather' . bin2hex(random_bytes(8));
            $body = '';
            foreach (array_filter([
                'location' => $request->input('location', 'Nigeria'),
                'crop'     => $request->input('crop', ''),
                'language' => $request->input('language', 'en'),
            ]) as $k => $v) {
                $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$k}\"\r\n\r\n{$v}\r\n";
            }
            $body .= "--{$boundary}--\r\n";

            $resp = $this->client()->post("{$base}/predict/weather", [
                'body'    => $body,
                'headers' => array_merge($this->headers(), ['Content-Type' => "multipart/form-data; boundary={$boundary}"]),
            ]);

            $data = json_decode((string) $resp->getBody(), true);
            if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300 && $data) {
                return response()->json($data);
            }
            return response()->json(['error' => 'Weather service unavailable'], 502);
        } catch (\Throwable $e) {
            Log::error('AI weather widget error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Weather service error'], 503);
        }
    }

    public function market(Request $request): JsonResponse
    {
        $request->validate([
            'crop'     => 'nullable|string|max:100',
            'region'   => 'nullable|string|max:200',
            'language' => 'nullable|string|max:10',
        ]);

        $base = $this->baseUrl();
        if (!$base) {
            return response()->json(['error' => 'AI engine not configured'], 503);
        }

        try {
            $boundary = '----MSASMarket' . bin2hex(random_bytes(8));
            $body = '';
            foreach (array_filter([
                'crop'     => $request->input('crop', ''),
                'region'   => $request->input('region', 'Nigeria'),
                'language' => $request->input('language', 'en'),
            ]) as $k => $v) {
                $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$k}\"\r\n\r\n{$v}\r\n";
            }
            $body .= "--{$boundary}--\r\n";

            $resp = $this->client()->post("{$base}/predict/market", [
                'body'    => $body,
                'headers' => array_merge($this->headers(), ['Content-Type' => "multipart/form-data; boundary={$boundary}"]),
            ]);

            $data = json_decode((string) $resp->getBody(), true);
            if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300 && $data) {
                return response()->json($data);
            }
            return response()->json(['error' => 'Market service unavailable'], 502);
        } catch (\Throwable $e) {
            Log::error('AI market widget error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Market service error'], 503);
        }
    }

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message'  => 'required|string|max:2000',
            'history'  => 'nullable|string',
            'language' => 'nullable|string|max:10',
        ]);

        $base = $this->baseUrl();
        if (!$base) {
            return response()->json(['error' => 'AI engine not configured'], 503);
        }

        try {
            $boundary = '----MSASChat' . bin2hex(random_bytes(8));
            $body = "--{$boundary}\r\nContent-Disposition: form-data; name=\"message\"\r\n\r\n" . $request->input('message') . "\r\n";

            if ($history = $request->input('history')) {
                $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"history\"\r\n\r\n{$history}\r\n";
            }

            $lang = $request->input('language', 'en');
            $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"language\"\r\n\r\n{$lang}\r\n";
            $body .= "--{$boundary}--\r\n";

            $resp = $this->client()->post("{$base}/chat", [
                'body'    => $body,
                'headers' => array_merge($this->headers(), ['Content-Type' => "multipart/form-data; boundary={$boundary}"]),
            ]);

            $data = json_decode((string) $resp->getBody(), true);
            if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300 && $data) {
                // Claude's default formatting (headers, **bold**, bullet
                // lists) reaches this point as raw Markdown — nothing in
                // the /chat prompt tells it not to use it, and neither web
                // nor mobile render Markdown. Normalizing here, once,
                // means every client (web widget, mobile screen) gets a
                // clean 'reply' plus structured 'sections' from the same
                // parse instead of each reimplementing this.
                if (!empty($data['reply']) && is_string($data['reply'])) {
                    $normalized = AiResponseNormalizer::normalize($data['reply']);
                    $data['reply']    = $normalized['reply'];
                    $data['sections'] = $normalized['sections'];
                }
                return response()->json($data);
            }
            return response()->json(['error' => 'Chat service unavailable'], 502);
        } catch (\Throwable $e) {
            Log::error('AI chat widget error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Chat service error'], 503);
        }
    }

    /**
     * Voice input for the AI Assistant. Audio in, transcript out — the
     * client puts the returned text into its own message box for the user
     * to review/edit before sending, exactly like typing. No AI/transcription
     * credentials are ever sent to or stored on the client; the OpenAI key
     * lives only here server-side (config('services.tts_openai.key'), the
     * same key already used for diagnosis-report TTS output).
     */
    public function transcribe(Request $request): JsonResponse
    {
        $request->validate([
            'audio'    => ['required', 'file', 'max:15360'], // 15MB
            'language' => ['nullable', 'string', 'max:10'],
        ]);

        $apiKey = config('services.tts_openai.key');
        if (!$apiKey) {
            return response()->json(['error' => 'Voice input is not configured on this server.'], 503);
        }

        try {
            $file = $request->file('audio');
            $multipart = [
                [
                    'name'     => 'file',
                    'contents' => fopen($file->getRealPath(), 'r'),
                    'filename' => $file->getClientOriginalName() ?: 'recording.m4a',
                ],
                ['name' => 'model', 'contents' => 'whisper-1'],
            ];
            // Whisper's `language` param is a hint, not an enforced output
            // language — it improves accuracy for the given spoken language
            // rather than translating; unsupported codes are simply ignored
            // by the API rather than erroring, so no per-language allow-list
            // is needed here.
            if ($lang = $request->input('language')) {
                $multipart[] = ['name' => 'language', 'contents' => $lang];
            }

            $resp = $this->client()->post('https://api.openai.com/v1/audio/transcriptions', [
                'multipart' => $multipart,
                'headers'   => ['Authorization' => "Bearer {$apiKey}"],
            ]);

            $data = json_decode((string) $resp->getBody(), true);
            if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300 && isset($data['text'])) {
                return response()->json(['text' => $data['text']]);
            }

            Log::warning('AI transcription non-200', ['status' => $resp->getStatusCode(), 'body' => substr((string) $resp->getBody(), 0, 300)]);
            return response()->json(['error' => "We couldn't understand the recording. Please try again in a quieter environment."], 502);
        } catch (\Throwable $e) {
            Log::error('AI transcription error', ['error' => $e->getMessage()]);
            return response()->json(['error' => "We couldn't understand the recording. Please try again in a quieter environment."], 503);
        }
    }
}
