<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::all();
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Skipping notifications.');
            return;
        }

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

        $now = Carbon::now('Africa/Dakar');
        $targetCount = min(50, max(12, $users->count() * 2));

        for ($i = 0; $i < $targetCount; $i++) {
            $user = $users->random();
            $template = $templates[array_rand($templates)];
            $createdAt = $now->copy()->subDays(rand(0, 45));
            $isRead = (bool) rand(0, 1);
            $readAt = $isRead ? $createdAt->copy()->addHours(rand(1, 72)) : null;

            Notification::create([
                'user_id' => $user->id,
                'type' => $template['type'],
                'channel' => $template['channel'],
                'title' => $template['title'],
                'message' => $template['message'],
                'metadata' => ['source' => 'seed'],
                'is_read' => $isRead,
                'read_at' => $readAt,
                'created_at' => $createdAt,
                'updated_at' => $readAt ?? $createdAt,
            ]);
        }
    }
}
