<?php

namespace App\Services\Notification;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsService
{
    /**
     * @param string|array<int, string> $recipients
     */
    /** @return array<int, array<string, mixed>> */
    public function send(string|array $recipients, string $message): array
    {
        $senderPhone = (string) config('services.orange_sms.sender_phone');
        if ($senderPhone === '') {
            throw new RuntimeException('ORANGE_SENDER_PHONE is not configured');
        }

        $senderName = (string) config('services.orange_sms.sender_name', 'CCAK');
        $baseUrl = rtrim((string) config('services.orange_sms.base_url', ''), '/');
        if ($baseUrl === '') {
            throw new RuntimeException('ORANGE_BASE_URL is not configured');
        }

        $token = $this->getAccessToken();
        $encodedSender = rawurlencode('tel:+' . ltrim($senderPhone, '+'));
        $url = "{$baseUrl}/smsmessaging/v1/outbound/{$encodedSender}/requests";

        $results = [];

        foreach (Arr::wrap($recipients) as $recipient) {
            $normalized = $this->normalizePhone($recipient);
            $payload = [
                'outboundSMSMessageRequest' => [
                    'address' => 'tel:+' . ltrim($normalized, '+'),
                    'senderAddress' => 'tel:+' . ltrim($senderPhone, '+'),
                    'senderName' => $senderName,
                    'outboundSMSTextMessage' => [
                        'message' => $message,
                    ],
                ],
            ];

            $response = Http::withToken($token)
                ->acceptJson()
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error('Orange SMS send failed', [
                    'recipient' => $recipient,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new RuntimeException('Orange SMS send failed');
            }

            $data = $response->json();
            $resourceUrl = is_array($data) ? ($data['resourceURL'] ?? null) : null;
            $resourceId = is_string($resourceUrl) ? basename($resourceUrl) : null;

            $results[] = [
                'recipient' => $normalized,
                'resource_url' => $resourceUrl,
                'resource_id' => $resourceId,
                'status' => $response->status(),
            ];
        }

        return $results;
    }

    private function getAccessToken(): string
    {
        $cacheKey = 'orange_sms_access_token';
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $baseUrl = rtrim((string) config('services.orange_sms.base_url', ''), '/');
        $appId = (string) config('services.orange_sms.app_id');
        $clientId = (string) config('services.orange_sms.client_id');
        $clientSecret = (string) config('services.orange_sms.client_secret');
        $authHeader = (string) config('services.orange_sms.auth_header');

        if ($appId === '' || $clientId === '' || $clientSecret === '') {
            throw new RuntimeException('Orange SMS credentials are not configured');
        }

        $headers = [];
        if ($authHeader !== '') {
            $headers['Authorization'] = $authHeader;
        }

        $response = Http::asForm()
            ->withHeaders($headers)
            ->post("{$baseUrl}/oauth/v3/token", [
                'grant_type' => 'client_credentials',
                'APPLICATION_ID' => $appId,
                'CLIENT_ID' => $clientId,
                'CLIENT_SECRET' => $clientSecret,
            ]);

        if (!$response->successful()) {
            Log::error('Orange SMS token request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException(
                'Failed to authenticate with Orange SMS. Status: ' . $response->status() . ' Body: ' . $response->body()
            );
        }

        $data = $response->json();
        $token = is_array($data) ? ($data['access_token'] ?? null) : null;
        $expiresIn = is_array($data) ? (int) ($data['expires_in'] ?? 0) : 0;

        if (!is_string($token) || $token === '') {
            throw new RuntimeException('Orange SMS access token missing');
        }

        $ttl = $expiresIn > 60 ? $expiresIn - 30 : 300;
        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }

    private function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/\\s+/', '', $phone) ?: $phone;
        if (str_starts_with($normalized, '+')) {
            return $normalized;
        }

        $countryCode = (string) config('services.orange_sms.default_country_code', '+221');
        return $countryCode . ltrim($normalized, '0');
    }
}
