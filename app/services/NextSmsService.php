<?php

namespace App\Services;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NextSmsService
{
    protected string $username;
    protected string $password;
    protected string $senderId;
    protected bool $testMode;
    protected string $baseUrl;

    public function __construct()
    {
        $this->username = (string) config('services.nextsms.username');
        $this->password = (string) config('services.nextsms.password');
        $this->senderId = (string) config('services.nextsms.sender_id', 'MUST');
        $this->testMode = (bool) config('services.nextsms.test_mode', false);
        $this->baseUrl  = rtrim((string) config('services.nextsms.base_url', 'https://messaging-service.co.tz'), '/');
    }

    public function sendSms(string $phone, string $message, $smsable = null): array
    {
        $originalPhone = $phone;
        $phone = $this->formatPhone($phone);

        if (! $phone) {
            Log::warning('NextSMS: invalid phone', ['phone' => $originalPhone]);

            return [
                'ok' => false,
                'message' => 'Invalid phone number',
                'reference' => null,
                'provider_message_id' => null,
                'provider_send_reference' => null,
                'response' => null,
            ];
        }

        $endpoint = $this->testMode
            ? $this->baseUrl . '/api/sms/v1/test/text/single'
            : $this->baseUrl . '/api/sms/v1/text/single';

        $localReference = 'NETFY-' . strtoupper(Str::random(10));

        $payload = [
            'from' => $this->senderId,
            'to' => [$phone],
            'text' => $message,
            'reference' => $localReference,
        ];

        try {
            $res = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode("{$this->username}:{$this->password}"),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(60)->connectTimeout(30)->post($endpoint, $payload);

            $body = $res->json() ?? [];
            $first = $body['messages'][0] ?? [];

            Log::info('NextSMS response', [
                'ok' => $res->successful(),
                'status' => $res->status(),
                'body' => $body ?: $res->body(),
            ]);

            return [
                'ok' => $res->successful(),
                'message' => $res->successful() ? 'SMS accepted by provider' : 'SMS failed',
                'reference' => $localReference,
                'provider_message_id' => isset($first['messageId']) ? (string) $first['messageId'] : null,
                'provider_send_reference' => isset($first['sendReference']) ? (string) $first['sendReference'] : null,
                'response' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('NextSMS Exception', ['error' => $e->getMessage()]);

            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'reference' => $localReference,
                'provider_message_id' => null,
                'provider_send_reference' => null,
                'response' => null,
            ];
        }
    }
    private function formatPhone(string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', $input);

        if (str_starts_with($digits, '255') && strlen($digits) === 12) {
            return $digits;
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '255' . substr($digits, 1);
        }

        if (strlen($digits) === 9 && (str_starts_with($digits, '6') || str_starts_with($digits, '7'))) {
            return '255' . $digits;
        }

        return null;
    }
}
