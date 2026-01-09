<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $templates = [
            [
                'type' => Notification::TYPE_WELCOME,
                'title' => 'Bienvenue a UCAK',
                'message' => 'Votre compte est actif. Veuillez completer votre dossier.',
                'channel' => Notification::CHANNEL_IN_APP,
            ],
            [
                'type' => Notification::TYPE_ENROLLMENT_CONFIRMED,
                'title' => 'Inscription confirmee',
                'message' => 'Votre inscription pour l annee academique est confirmee.',
                'channel' => Notification::CHANNEL_IN_APP,
            ],
            [
                'type' => Notification::TYPE_GRADE_PUBLISHED,
                'title' => 'Notes publiees',
                'message' => 'Les notes du semestre sont disponibles dans votre espace.',
                'channel' => Notification::CHANNEL_IN_APP,
            ],
            [
                'type' => Notification::TYPE_DOCUMENT_READY,
                'title' => 'Document disponible',
                'message' => 'Votre document est disponible au service de la scolarite.',
                'channel' => Notification::CHANNEL_IN_APP,
            ],
            [
                'type' => Notification::TYPE_SYSTEM,
                'title' => 'Annonce scolarite',
                'message' => 'La plateforme sera en maintenance ce week end.',
                'channel' => Notification::CHANNEL_IN_APP,
            ],
        ];
        $template = $templates[array_rand($templates)];

        return [
            'user_id' => User::factory(),
            'type' => $template['type'],
            'channel' => $template['channel'],
            'title' => $template['title'],
            'message' => $template['message'],
            'metadata' => [
                'source' => 'factory',
            ],
            'is_read' => false,
            'read_at' => null,
        ];
    }
}
