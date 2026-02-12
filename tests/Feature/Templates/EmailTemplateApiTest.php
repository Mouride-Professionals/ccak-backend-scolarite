<?php

declare(strict_types=1);

namespace Tests\Feature\Templates;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTemplateApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Authenticate::class);
    }

    public function test_can_list_email_templates_with_contract_shape(): void
    {
        $this->getJson('/api/v1/templates/email')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['name', 'display_name', 'description', 'variables', 'path'],
                ],
            ]);
    }

    public function test_can_preview_email_template(): void
    {
        $this->postJson('/api/v1/templates/email/preview', [
            'name' => 'notification',
            'variables' => [
                'title' => 'Test Notification',
                'message' => 'This is a preview message.',
                'user' => [
                    'name' => 'Aminata',
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'notification')
            ->assertJsonStructure([
                'data' => ['name', 'path', 'html'],
            ]);
    }

    public function test_preview_requires_name_or_path(): void
    {
        $this->postJson('/api/v1/templates/email/preview', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'errors' => ['name'],
            ]);
    }
}
