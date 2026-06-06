<?php

// app/Mail/NotificationMail.php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Notification $notification
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->notification->title,
        );
    }

    public function content(): Content
    {
        $view = match ($this->notification->type) {
            Notification::TYPE_WELCOME => 'emails.notifications.welcome',
            Notification::TYPE_PASSWORD_RESET => 'emails.notifications.password-reset',
            Notification::TYPE_GRADE_PUBLISHED => 'emails.notifications.grade-published',
            Notification::TYPE_ENROLLMENT_CONFIRMED => 'emails.notifications.enrollment-confirmed',
            Notification::TYPE_DOCUMENT_READY => 'emails.notifications.document-ready',
            default => 'emails.notifications.generic',
        };

        return new Content(
            view: $view,
            with: [
                'notification' => $this->notification,
                'user' => $this->notification->user,
                'metadata' => $this->notification->metadata,
            ],
        );
    }
}
