<?php

namespace App\Services\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Log;

class DocumentNotificationService
{
    private bool $enabled;
    /** @var array<int, string> */
    private array $channels;

    public function __construct()
    {
        $this->enabled = config('documents.notifications.enabled', true);
        $this->channels = config('documents.notifications.channels', ['database']);
    }

    /**
     * Notifier l'upload d'un document
     */
    public function notifyUpload(Document $document): void
    {
        if (!$this->enabled || !config('documents.notifications.on_upload', true)) {
            return;
        }

        $this->log('Document uploaded', $document);
        $this->sendToChannels('upload', $document);
    }

    /**
     * Notifier l'approbation d'un document
     */
    public function notifyApproval(Document $document): void
    {
        if (!$this->enabled || !config('documents.notifications.on_approval', true)) {
            return;
        }

        $this->log('Document approved', $document);
        $this->sendToChannels('approval', $document);
    }

    /**
     * Notifier le rejet d'un document
     */
    public function notifyRejection(Document $document, string $reason): void
    {
        if (!$this->enabled || !config('documents.notifications.on_rejection', true)) {
            return;
        }

        $this->log('Document rejected', $document, ['reason' => $reason]);
        $this->sendToChannels('rejection', $document, ['reason' => $reason]);
    }

    /**
     * Envoyer aux canaux configurés
     */
    /** @param array<string, mixed> $data */
    private function sendToChannels(string $event, Document $document, array $data = []): void
    {
        foreach ($this->channels as $channel) {
            try {
                $this->sendToChannel($channel, $event, $document, $data);
            } catch (\Exception $e) {
                Log::error("Failed to send notification to channel {$channel}", [
                    'event' => $event,
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Envoyer à un canal spécifique
     */
    /** @param array<string, mixed> $data */
    private function sendToChannel(string $channel, string $event, Document $document, array $data): void
    {
        switch ($channel) {
            case 'database':
                $this->sendToDatabase($event, $document, $data);
                break;

            case 'email':
                $this->sendEmail($event, $document, $data);
                break;

            case 'sms':
                $this->sendSms($event, $document, $data);
                break;

            default:
                Log::warning("Unknown notification channel: {$channel}");
        }
    }

    /**
     * Envoyer à la base de données (notifications internes)
     */
    /** @param array<string, mixed> $data */
    private function sendToDatabase(string $event, Document $document, array $data): void
    {
        // Créer une notification dans la table notifications
        // (si vous utilisez le système de notifications de Laravel)
        $document->student?->notify(
            new \App\Notifications\DocumentStatusChanged($event, $document, $data)
        );
    }

    /**
     * Envoyer un email
     */
    /** @param array<string, mixed> $data */
    private function sendEmail(string $event, Document $document, array $data): void
    {
        // TODO: Implémenter l'envoi d'email
        // Mail::to($document->student->email)->send(...);
    }

    /**
     * Envoyer un SMS
     */
    /** @param array<string, mixed> $data */
    private function sendSms(string $event, Document $document, array $data): void
    {
        // TODO: Implémenter l'envoi de SMS
        // Utiliser un service comme Twilio, etc.
    }

    /**
     * Journaliser la notification
     */
    /** @param array<string, mixed> $context */
    private function log(string $message, Document $document, array $context = []): void
    {
        Log::info($message, array_merge([
            'document_id' => $document->id,
            'student_id' => $document->student_id,
            'type' => $document->type->value,
            'status' => $document->status->value,
        ], $context));
    }
}
