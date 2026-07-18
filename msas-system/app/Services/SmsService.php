<?php

namespace App\Services;

use App\Traits\NormalizesPhone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    use NormalizesPhone;

    /**
     * Send an SMS and return a structured result array:
     *   ['success' => bool, 'provider' => string, 'message_id' => string|null,
     *    'error' => string|null, 'raw' => array]
     */
    public function send(string $phone, string $message): array
    {
        $driver = config('services.sms.driver', 'log');
        $phone  = $this->normalizePhone($phone);

        $result = match ($driver) {
            'termii'          => $this->sendTermii($phone, $message),
            'africas_talking' => $this->sendAfricasTalking($phone, $message),
            'twilio'          => $this->sendTwilio($phone, $message),
            default           => $this->sendLog($phone, $message),
        };

        // Unified audit log entry
        Log::channel('single')->info('SMS delivery attempt', [
            'provider'   => $driver,
            'phone'      => $phone,
            'success'    => $result['success'],
            'message_id' => $result['message_id'] ?? null,
            'error'      => $result['error'] ?? null,
        ]);

        return $result;
    }

    // ── Providers ────────────────────────────────────────────────────────────

    private function sendTermii(string $phone, string $message): array
    {
        $apiKey = config('services.sms.termii.api_key', '');

        if (empty($apiKey) || in_array(strtoupper($apiKey), ['REPLACE', 'YOUR_KEY', 'YOUR-KEY', ''])) {
            Log::warning('Termii API key is not configured — SMS cannot be sent.');
            return $this->failure('termii', 'Termii API key is not configured.');
        }

        try {
            $payload = [
                'api_key' => $apiKey,
                'to'      => $phone,
                'from'    => config('services.sms.termii.from', 'MSAS'),
                'sms'     => $message,
                'type'    => 'plain',
                'channel' => config('services.sms.termii.channel', 'generic'),
            ];

            $response = Http::timeout(15)->post('https://api.ng.termii.com/api/sms/send', $payload);

            $body = $response->json() ?? [];

            Log::debug('Termii API response', [
                'status'   => $response->status(),
                'response' => $body,
                'phone'    => $phone,
            ]);

            // Termii returns 200 even on auth errors; check body code
            $success = $response->successful()
                && isset($body['message_id'])
                && (! isset($body['code']) || strtolower($body['code']) === 'ok');

            if (! $success) {
                $error = $body['message'] ?? $body['error'] ?? 'Unknown Termii error (HTTP ' . $response->status() . ')';
                Log::error('Termii SMS rejected', ['phone' => $phone, 'error' => $error, 'body' => $body]);
                return $this->failure('termii', $error, $body);
            }

            return $this->success('termii', $body['message_id'] ?? null, $body);

        } catch (\Throwable $e) {
            Log::error('Termii SMS exception', ['phone' => $phone, 'error' => $e->getMessage()]);
            return $this->failure('termii', $e->getMessage());
        }
    }

    private function sendAfricasTalking(string $phone, string $message): array
    {
        $apiKey = config('services.sms.africas_talking.api_key', '');

        if (empty($apiKey) || strtoupper($apiKey) === 'REPLACE') {
            return $this->failure('africas_talking', "Africa's Talking API key is not configured.");
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'apiKey' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->asForm()
                ->post('https://api.africastalking.com/version1/messaging', [
                    'username' => config('services.sms.africas_talking.username'),
                    'to'       => $phone,
                    'message'  => $message,
                    'from'     => config('services.sms.africas_talking.from', 'MSAS'),
                ]);

            $body = $response->json() ?? [];

            Log::debug("Africa's Talking API response", ['status' => $response->status(), 'body' => $body, 'phone' => $phone]);

            $recipients = $body['SMSMessageData']['Recipients'] ?? [];
            $first      = $recipients[0] ?? [];
            $status     = strtolower($first['status'] ?? '');
            $success    = $response->successful() && in_array($status, ['success', 'sent']);

            if (! $success) {
                $error = $first['status'] ?? ($body['SMSMessageData']['Message'] ?? 'Unknown AT error');
                Log::error("Africa's Talking SMS rejected", ['phone' => $phone, 'error' => $error]);
                return $this->failure('africas_talking', $error, $body);
            }

            return $this->success('africas_talking', $first['messageId'] ?? null, $body);

        } catch (\Throwable $e) {
            Log::error("Africa's Talking SMS exception", ['phone' => $phone, 'error' => $e->getMessage()]);
            return $this->failure('africas_talking', $e->getMessage());
        }
    }

    private function sendTwilio(string $phone, string $message): array
    {
        $sid   = config('services.sms.twilio.sid', '');
        $token = config('services.sms.twilio.token', '');
        $from  = config('services.sms.twilio.from', '');

        if (empty($sid) || empty($token) || strtoupper($sid) === 'REPLACE') {
            return $this->failure('twilio', 'Twilio credentials are not configured.');
        }

        try {
            $response = Http::timeout(15)
                ->withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To'   => $phone,
                    'Body' => $message,
                ]);

            $body = $response->json() ?? [];

            Log::debug('Twilio API response', ['status' => $response->status(), 'sid_prefix' => substr($sid, 0, 8) . '…', 'body' => $body, 'phone' => $phone]);

            if (! $response->successful() || isset($body['code'])) {
                $error = $body['message'] ?? 'Twilio error (HTTP ' . $response->status() . ')';
                Log::error('Twilio SMS rejected', ['phone' => $phone, 'error' => $error]);
                return $this->failure('twilio', $error, $body);
            }

            return $this->success('twilio', $body['sid'] ?? null, $body);

        } catch (\Throwable $e) {
            Log::error('Twilio SMS exception', ['phone' => $phone, 'error' => $e->getMessage()]);
            return $this->failure('twilio', $e->getMessage());
        }
    }

    private function sendLog(string $phone, string $message): array
    {
        Log::info('SMS [log driver — not sent to real network]', ['to' => $phone, 'message' => $message]);
        return $this->success('log', 'LOG-' . uniqid());
    }

    // ── Result helpers ────────────────────────────────────────────────────────

    private function success(string $provider, ?string $messageId, array $raw = []): array
    {
        return ['success' => true,  'provider' => $provider, 'message_id' => $messageId, 'error' => null, 'raw' => $raw];
    }

    private function failure(string $provider, string $error, array $raw = []): array
    {
        return ['success' => false, 'provider' => $provider, 'message_id' => null, 'error' => $error, 'raw' => $raw];
    }
}
