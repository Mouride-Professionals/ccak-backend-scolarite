<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sms:test {phone} {message?}', function (string $phone, ?string $message = null) {
    $smsService = app(\App\Services\Notification\SmsService::class);
    try {
        $smsService->send($phone, $message ?? 'Test SMS from CCAK');
        $this->info('SMS sent.');
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return \Symfony\Component\Console\Command\Command::FAILURE;
    }
})->purpose('Send a test SMS using the configured SMS provider.');

Schedule::command('sync:ccak-students')->dailyAt('00:00');
