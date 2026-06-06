<?php

namespace Tests\Feature\Academic;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class DepartmentApiTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    private array $permissions = [
        'departments.view',
        'departments.create',
        'departments.update',
        'departments.delete',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_can_crud_departments(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $faculty = Faculty::factory()->create();
        $head = User::factory()->create();

        $payload = [
            'faculty_id' => $faculty->id,
            'name' => 'Computer Science',
            'code' => 'CS',
            'head_id' => $head->id,
            'is_active' => true,
        ];

        $createResponse = $this->postJson('/api/v1/departments', $payload);
        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Computer Science')
            ->assertJsonPath('data.code', 'CS')
            ->assertJsonPath('data.faculty_id', $faculty->id)
            ->assertJsonPath('data.head_id', $head->id);

        $departmentId = $createResponse->json('data.id');

        $this->assertDatabaseHas('departments', [
            'id' => $departmentId,
            'code' => 'CS',
        ]);

        $this->getJson('/api/v1/departments')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/v1/departments/{$departmentId}")
            ->assertOk()
            ->assertJsonPath('data.id', $departmentId);

        $this->putJson("/api/v1/departments/{$departmentId}", [
            'name' => 'Computing',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Computing');

        $this->deleteJson("/api/v1/departments/{$departmentId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('departments', [
            'id' => $departmentId,
        ]);
    }

    public function test_department_requires_faculty(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $this->postJson('/api/v1/departments', [
            'name' => 'Mathematics',
            'code' => 'MATH',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['faculty_id']);
    }

    public function test_department_requires_permission(): void
    {
        $this->seedPermissions($this->permissions);

        $this->actingAs(User::factory()->create());

        $this->getJson('/api/v1/departments')
            ->assertStatus(403);
    }
}
