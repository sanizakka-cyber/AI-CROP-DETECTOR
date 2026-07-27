<?php

namespace App\Http\Controllers;

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
                return response()->json($data);
            }
            return response()->json(['error' => 'Chat service unavailable'], 502);
        } catch (\Throwable $e) {
            Log::error('AI chat widget error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Chat service error'], 503);
        }
    }
}
