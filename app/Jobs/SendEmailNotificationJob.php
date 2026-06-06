<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use App\Services\Notification\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Notification $notification
    ) {}

    public function handle(EmailService $emailService): void
    {
        /** @var User $user */
        $user = $this->notification->user;

        if (! $user->email) {
            return;
        }

        $emailService->send(
            recipients: $user->email,
            subject: $this->notification->title,
            message: $this->notification->message,
            template: 'notification',
            templateData: $this->notification->metadata ?? []
        );
    }
}
