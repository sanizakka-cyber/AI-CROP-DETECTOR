<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $phone, string $message): bool
    {
        $driver = config('services.sms.driver', 'log');

        return match($driver) {
            'termii'         => $this->sendTermii($phone, $message),
            'africas_talking'=> $this->sendAfricasTalking($phone, $message),
            'twilio'         => $this->sendTwilio($phone, $message),
            default          => $this->sendLog($phone, $message),
        };
    }

    private function sendTermii(string $phone, string $message): bool
    {
        try {
            $response = Http::post('https://api.ng.termii.com/api/sms/send', [
                'api_key'  => config('services.sms.termii.api_key'),
                'to'       => $this->normalizeNigerianPhone($phone),
                'from'     => config('services.sms.termii.from', 'MSAS'),
                'sms'      => $message,
                'type'     => 'plain',
                'channel'  => config('services.sms.termii.channel', 'generic'),
            ]);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Termii SMS failed', ['phone' => $phone, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function sendAfricasTalking(string $phone, string $message): bool
    {
        try {
            $response = Http::withHeaders([
                'apiKey' => config('services.sms.africas_talking.api_key'),
                'Accept' => 'application/json',
            ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
                'username' => config('services.sms.africas_talking.username'),
                'to'       => $phone,
                'message'  => $message,
                'from'     => config('services.sms.africas_talking.from', 'MSAS'),
            ]);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Africa's Talking SMS failed", ['phone' => $phone, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function sendTwilio(string $phone, string $message): bool
    {
        try {
            $sid   = config('services.sms.twilio.sid');
            $token = config('services.sms.twilio.token');
            $from  = config('services.sms.twilio.from');

            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To'   => $phone,
                    'Body' => $message,
                ]);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Twilio SMS failed', ['phone' => $phone, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function sendLog(string $phone, string $message): bool
    {
        Log::info('SMS (log driver)', ['to' => $phone, 'message' => $message]);
        return true;
    }

    private function normalizeNigerianPhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (str_starts_with($clean, '0')) {
            $clean = '234' . substr($clean, 1);
        }
        return '+' . ltrim($clean, '+');
    }
}
