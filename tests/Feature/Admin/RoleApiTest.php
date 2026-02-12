<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class RoleApiTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    private array $permissions = [
        'roles.view',
        'roles.manage',
        'faculties.view',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_can_manage_roles(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $createResponse = $this->postJson('/api/v1/roles', [
            'name' => 'EDITOR',
            'permissions' => ['faculties.view'],
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'EDITOR');

        $roleId = $createResponse->json('data.id');

        $this->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $this->putJson("/api/v1/roles/{$roleId}", [
            'name' => 'EDITOR_UPDATED',
            'permissions' => [],
        ])->assertOk()
            ->assertJsonPath('data.name', 'EDITOR_UPDATED');
    }

    public function test_roles_require_permission(): void
    {
        $this->seedPermissions($this->permissions);

        $this->actingAs(User::factory()->create());

        Role::create(['name' => 'VIEWER', 'guard_name' => config('auth.defaults.guard', 'api')]);

        $this->getJson('/api/v1/roles')
            ->assertStatus(403);
    }
}
