<?php

declare(strict_types=1);

namespace App\Services\Templates;

use Illuminate\Support\Facades\View;

class EmailTemplateCatalog
{
    /**
     * @return array<int, array{name: string, display_name: string, description: string, variables: array<int, string>, path: string}>
     */
    public function all(): array
    {
        $templates = [
            [
                'name' => 'notification',
                'display_name' => 'Notification generique',
                'description' => 'Template pour les notifications generiques',
                'variables' => ['title', 'message', 'user.name'],
                'path' => 'emails.notifications.generic',
            ],
            [
                'name' => 'welcome',
                'display_name' => 'Message de bienvenue',
                'description' => 'Template de bienvenue pour les nouveaux utilisateurs',
                'variables' => ['user.name'],
                'path' => 'emails.notifications.welcome',
            ],
            [
                'name' => 'password_reset',
                'display_name' => 'Reinitialisation mot de passe',
                'description' => 'Template de reinitialisation de mot de passe',
                'variables' => ['user.name', 'metadata.reset_url'],
                'path' => 'emails.notifications.password-reset',
            ],
            [
                'name' => 'grade_published',
                'display_name' => 'Publication de note',
                'description' => 'Template de notification de publication de note',
                'variables' => ['user.name', 'metadata.subject', 'metadata.score'],
                'path' => 'emails.notifications.grade-published',
            ],
            [
                'name' => 'enrollment_confirmed',
                'display_name' => 'Confirmation inscription',
                'description' => 'Template de confirmation d\'inscription',
                'variables' => ['user.name', 'metadata.course_name', 'metadata.enrollment_id'],
                'path' => 'emails.notifications.enrollment-confirmed',
            ],
            [
                'name' => 'document_ready',
                'display_name' => 'Document disponible',
                'description' => 'Template pour document pret au telechargement',
                'variables' => ['user.name', 'metadata.document_name', 'metadata.download_url'],
                'path' => 'emails.notifications.document-ready',
            ],
        ];

        return array_values(array_filter(
            $templates,
            static fn (array $template): bool => View::exists($template['path'])
        ));
    }

    /**
     * @return array{name: string, display_name: string, description: string, variables: array<int, string>, path: string}|null
     */
    public function findByNameOrPath(?string $name, ?string $path): ?array
    {
        $templates = $this->all();

        foreach ($templates as $template) {
            if ($name !== null && $template['name'] === $name) {
                return $template;
            }

            if ($path !== null && $template['path'] === $path) {
                return $template;
            }
        }

        return null;
    }
}
