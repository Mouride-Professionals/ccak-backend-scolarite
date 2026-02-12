<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicProgram;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class AcademicProgramApiTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    private array $permissions = [
        'academic_programs.view',
        'academic_programs.create',
        'academic_programs.update',
        'academic_programs.delete',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_can_crud_academic_programs(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $department = Department::factory()->create();

        $payload = [
            'department_id' => $department->id,
            'name' => 'Computer Engineering',
            'level' => AcademicProgram::LEVELS[0],
            'duration_semesters' => 6,
            'total_credits_required' => 180,
            'is_active' => true,
        ];

        $createResponse = $this->postJson('/api/v1/academic-programs', $payload);
        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Computer Engineering')
            ->assertJsonPath('data.level', AcademicProgram::LEVELS[0])
            ->assertJsonPath('data.department_id', $department->id);

        $programId = $createResponse->json('data.id');

        $this->assertDatabaseHas('academic_programs', [
            'id' => $programId,
            'level' => AcademicProgram::LEVELS[0],
        ]);

        $this->getJson('/api/v1/academic-programs')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/v1/academic-programs/{$programId}")
            ->assertOk()
            ->assertJsonPath('data.id', $programId);

        $this->putJson("/api/v1/academic-programs/{$programId}", [
            'level' => AcademicProgram::LEVELS[1],
        ])->assertOk()
            ->assertJsonPath('data.level', AcademicProgram::LEVELS[1]);

        $this->deleteJson("/api/v1/academic-programs/{$programId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('academic_programs', [
            'id' => $programId,
        ]);
    }

    public function test_academic_program_requires_valid_level(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $department = Department::factory()->create();

        $this->postJson('/api/v1/academic-programs', [
            'department_id' => $department->id,
            'name' => 'Invalid Program',
            'level' => 'PHD',
            'duration_semesters' => 6,
            'total_credits_required' => 180,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['level']);
    }

    public function test_academic_program_requires_permission(): void
    {
        $this->seedPermissions($this->permissions);

        $this->actingAs(User::factory()->create());

        $this->getJson('/api/v1/academic-programs')
            ->assertStatus(403);
    }
}
