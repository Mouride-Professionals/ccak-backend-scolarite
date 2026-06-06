<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use App\Services\Notification\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public Notification $notification
    ) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(SmsService $smsService): void
    {
        /** @var User $user */
        $user = $this->notification->user;

        // Check if user has a phone number
        $phone = $user->phone ?? $user->student?->phone;
        if (! $phone) {
            $this->notification->update(['sms_status' => 'skipped']);

            return;
        }

        $results = $smsService->send(
            recipients: $phone,
            message: $this->notification->message
        );

        $this->notification->update([
            'sms_status' => 'sent',
            'metadata' => array_merge($this->notification->metadata ?? [], [
                'sms' => $results,
            ]),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->notification->update([
            'sms_status' => 'failed',
            'metadata' => array_merge($this->notification->metadata ?? [], [
                'sms_error' => $exception->getMessage(),
            ]),
        ]);
    }
}
