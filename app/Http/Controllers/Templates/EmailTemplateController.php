<?php

declare(strict_types=1);

namespace App\Http\Controllers\Templates;

use App\Http\Controllers\Controller;
use App\Http\Requests\Templates\PreviewEmailTemplateRequest;
use App\Services\Templates\EmailTemplateCatalog;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\View;

#[Group('Templates', 'Email templates for dashboard template management.')]
class EmailTemplateController extends Controller
{
    public function __construct(
        private readonly EmailTemplateCatalog $catalog,
    ) {}

    #[Endpoint(operationId: 'emailTemplatesList')]
    #[Response(status: 200, description: 'Email template catalog', examples: [
        'success' => true,
        'data' => [
            [
                'name' => 'notification',
                'display_name' => 'Notification generique',
                'description' => 'Template pour les notifications generiques',
                'variables' => ['title', 'message', 'user.name'],
                'path' => 'emails.notifications.generic',
            ],
        ],
    ])]
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->catalog->all(),
        ]);
    }

    #[Endpoint(operationId: 'emailTemplatesPreview')]
    #[Response(status: 200, description: 'Rendered HTML preview for an email template', examples: [
        'success' => true,
        'data' => [
            'name' => 'notification',
            'path' => 'emails.notifications.generic',
            'html' => '<html>...</html>',
        ],
    ])]
    public function preview(PreviewEmailTemplateRequest $request): JsonResponse
    {
        $template = $this->catalog->findByNameOrPath(
            $request->input('name'),
            $request->input('path')
        );

        if ($template === null) {
            return response()->json([
                'success' => false,
                'message' => 'Email template not found.',
                'errors' => [],
            ], 404);
        }

        $variables = $request->input('variables', []);
        $user = (object) [
            'name' => (string) data_get($variables, 'user.name', 'Utilisateur'),
        ];

        $notification = (object) [
            'title' => (string) data_get($variables, 'title', 'Apercu de notification'),
            'message' => (string) data_get($variables, 'message', 'Contenu de demonstration.'),
        ];

        $metadata = array_merge([
            'reset_url' => (string) config('app.url', 'http://localhost') . '/reset-password/demo',
            'subject' => 'Mathematiques',
            'score' => '16',
            'course_name' => 'Programmation Web',
            'enrollment_id' => 'demo-enrollment',
            'document_name' => 'Attestation',
            'download_url' => (string) config('app.url', 'http://localhost') . '/documents/demo/download',
        ], (array) data_get($variables, 'metadata', []));

        $html = View::make($template['path'], [
            'user' => $user,
            'notification' => $notification,
            'metadata' => $metadata,
            'title' => $notification->title,
            'message' => $notification->message,
        ])->render();

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $template['name'],
                'path' => $template['path'],
                'html' => $html,
            ],
        ]);
    }
}
