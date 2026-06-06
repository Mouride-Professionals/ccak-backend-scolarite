<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class UserRoleApiTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    private array $permissions = [
        'users.assign_roles',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_can_assign_roles_to_user(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        Role::create(['name' => 'STUDENT', 'guard_name' => config('auth.defaults.guard', 'api')]);

        $user = User::factory()->create();

        $this->putJson("/api/v1/users/{$user->id}/roles", [
            'roles' => ['STUDENT'],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id);

        $this->assertTrue($user->fresh()->hasRole('STUDENT'));
    }

    public function test_assign_roles_requires_permission(): void
    {
        $this->seedPermissions($this->permissions);

        $this->actingAs(User::factory()->create());

        Role::create(['name' => 'STUDENT', 'guard_name' => config('auth.defaults.guard', 'api')]);

        $user = User::factory()->create();

        $this->putJson("/api/v1/users/{$user->id}/roles", [
            'roles' => ['STUDENT'],
        ])->assertStatus(403);
    }
}
