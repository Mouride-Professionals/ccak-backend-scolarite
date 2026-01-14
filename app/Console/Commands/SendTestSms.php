<?php

namespace App\Console\Commands;

use App\Services\Notification\SmsService;
use Illuminate\Console\Command;

class SendTestSms extends Command
{
    protected $signature = 'sms:test {phone} {message?}';
    protected $description = 'Send a test SMS using the configured SMS provider.';

    public function handle(SmsService $smsService): int
    {
        $phone = (string) $this->argument('phone');
        $message = (string) ($this->argument('message') ?? 'Test SMS from CCAK');

        try {
            $smsService->send($phone, $message);
        } catch (\Throwable $e) {
            $this->error('SMS failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('SMS sent.');
        return self::SUCCESS;
    }
}
