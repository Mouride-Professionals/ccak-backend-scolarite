<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentStatusChanged extends Notification
{
    use Queueable;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly string $event,
        private readonly Document $document,
        private readonly array $data = []
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'document_id' => $this->document->id,
            'student_id' => $this->document->student_id,
            'type' => $this->document->type?->value,
            'status' => $this->document->status?->value,
            'metadata' => $this->data,
        ];
    }
}
