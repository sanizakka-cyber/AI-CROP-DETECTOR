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

        $file = $request->file('audio');
        Log::info('TRANSCRIBE_REQUEST', [
            'user_id'     => $request->user()?->id,
            'mime'        => $file->getMimeType(),
            'size'        => $file->getSize(),
            'model'       => 'whisper-1',
            // Prefix only (e.g. "sk-proj-abCD") — enough to tell two keys
            // apart without exposing anything usable; never the full value.
            'key_prefix'  => substr($apiKey, 0, 11) . '…',
        ]);

        try {
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

            $resp   = $this->client()->post('https://api.openai.com/v1/audio/transcriptions', [
                'multipart' => $multipart,
                'headers'   => ['Authorization' => "Bearer {$apiKey}"],
            ]);
            $status = $resp->getStatusCode();
            $data   = json_decode((string) $resp->getBody(), true);

            if ($status >= 200 && $status < 300) {
                // Whisper can return 200 + "" (empty string) for audio with
                // no detectable speech — a real, successful response, not a
                // failure. Map that to its own 422 rather than silently
                // returning {"text":""} for the client to puzzle over.
                $text = $data['text'] ?? null;
                Log::info('TRANSCRIBE_RESULT', ['user_id' => $request->user()?->id, 'openai_status' => $status, 'has_text' => (bool) $text]);
                if ($text === null) {
                    return response()->json(['error' => 'No understandable speech detected.'], 422);
                }
                if (trim($text) === '') {
                    return response()->json(['error' => 'No understandable speech detected.'], 422);
                }
                return response()->json(['text' => $text]);
            }

            $openaiError = $data['error']['message'] ?? null;
            $openaiType  = $data['error']['type']    ?? null;
            $openaiCode  = $data['error']['code']    ?? null;
            // Safe to surface: OpenAI's own response headers, not derived
            // from the key itself. request-id is what OpenAI support asks
            // for when diagnosing a billing/quota error from the outside;
            // organization only appears when the key belongs to one.
            $requestId    = $resp->getHeaderLine('x-request-id') ?: null;
            $organization = $resp->getHeaderLine('openai-organization') ?: null;
            Log::warning('TRANSCRIBE_OPENAI_ERROR', [
                'user_id'       => $request->user()?->id,
                'openai_status' => $status,
                'openai_type'   => $openaiType,
                'openai_code'   => $openaiCode,
                'openai_error'  => $openaiError,
                'request_id'    => $requestId,
                'organization'  => $organization,
                'key_prefix'    => substr($apiKey, 0, 11) . '…',
            ]);

            // openai_type/openai_error are generic gateway-level strings
            // (e.g. "insufficient_quota", "rate_limit_exceeded") — never a
            // key, token, or anything OpenAI itself treats as sensitive.
            // Surfacing them (not to the farmer-facing message, only this
            // extra field) is what let this exact defect get root-caused
            // without any server log access.
            //
            // 'insufficient_quota' is OpenAI's billing-exhaustion error, not
            // a transient rate limit — it arrives as HTTP 429 same as an
            // actual rate limit, but "try again shortly" is actively wrong
            // advice here: retrying will never succeed until the OpenAI
            // account's billing/quota is topped up. Distinct message so
            // this doesn't read as a self-resolving blip.
            // TEMPORARY — billing/project diagnostic only, being removed in
            // the immediate follow-up commit once the root cause is
            // confirmed. Never the key itself, only a prefix + OpenAI's own
            // response headers, but still more than a farmer-facing error
            // needs permanently.
            $diagnostic = [
                'openai_code'  => $openaiCode,
                'request_id'   => $requestId,
                'organization' => $organization,
                'key_prefix'   => substr($apiKey, 0, 11) . '…',
            ];

            if ($status === 429 && $openaiType === 'insufficient_quota') {
                return response()->json(['error' => 'Voice input is temporarily unavailable. Please type your question instead.', 'error_detail' => $openaiType, 'diagnostic' => $diagnostic], 503);
            }

            return match (true) {
                $status === 400 => response()->json(['error' => 'Invalid or unsupported audio file.', 'error_detail' => $openaiType, 'diagnostic' => $diagnostic], 400),
                $status === 429 => response()->json(['error' => 'Speech service temporarily rate-limited. Please try again shortly.', 'error_detail' => $openaiType, 'diagnostic' => $diagnostic], 429),
                default         => response()->json(['error' => 'Speech service unavailable. Please try again shortly.', 'error_detail' => $openaiType, 'diagnostic' => $diagnostic], 502),
            };
        } catch (\Throwable $e) {
            Log::error('TRANSCRIBE_INTERNAL_ERROR', ['user_id' => $request->user()?->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Speech service unavailable. Please try again shortly.'], 503);
        }
    }
}
