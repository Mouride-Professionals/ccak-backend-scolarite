<?php

namespace App\Services\Notification;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

class EmailService
{
    /**
     * @param  string|array<int, string>  $recipients
     * @param  array<string, mixed>  $templateData
     */
    public function send(
        string|array $recipients,
        string $subject,
        string $message,
        string $template = 'emails.notifications.generic',
        array $templateData = []
    ): void {
        $view = $this->resolveView($template);
        $data = array_merge(['message' => $message], $templateData);

        if ($view !== null) {
            Mail::send($view, $data, function ($mail) use ($recipients, $subject): void {
                $mail->to(Arr::wrap($recipients))
                    ->subject($subject);
            });

            return;
        }

        Mail::raw($message, function ($mail) use ($recipients, $subject): void {
            $mail->to(Arr::wrap($recipients))
                ->subject($subject);
        });
    }

    private function resolveView(string $template): ?string
    {
        if (View::exists($template)) {
            return $template;
        }

        if ($template === 'notification' && View::exists('emails.notifications.generic')) {
            return 'emails.notifications.generic';
        }

        return View::exists('emails.notifications.generic') ? 'emails.notifications.generic' : null;
    }
}
