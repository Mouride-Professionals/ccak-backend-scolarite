<?php

namespace App\Services\Notification;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * @param string|array<int, string> $recipients
     */
    public function send(string|array $recipients, string $message): void
    {
        Log::info('SMS send requested', [
            'recipients' => Arr::wrap($recipients),
            'message' => $message,
        ]);
    }
}
