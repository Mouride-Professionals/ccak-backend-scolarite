<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicProgram;
use App\Models\CourseUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class CourseUnitApiTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    private array $permissions = [
        'course_units.view',
        'course_units.create',
        'course_units.update',
        'course_units.delete',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_can_crud_course_units(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $program = AcademicProgram::factory()->create();

        $payload = [
            'academic_program_id' => $program->id,
            'code' => 'UE101',
            'name' => 'Algorithms',
            'semester_number' => 1,
            'credits' => 6,
            'type' => CourseUnit::TYPES[0],
            'is_active' => true,
        ];

        $createResponse = $this->postJson('/api/v1/course-units', $payload);
        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Algorithms')
            ->assertJsonPath('data.code', 'UE101')
            ->assertJsonPath('data.academic_program_id', $program->id);

        $unitId = $createResponse->json('data.id');

        $this->assertDatabaseHas('course_units', [
            'id' => $unitId,
            'code' => 'UE101',
        ]);

        $this->getJson('/api/v1/course-units')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/v1/course-units/{$unitId}")
            ->assertOk()
            ->assertJsonPath('data.id', $unitId);

        $this->putJson("/api/v1/course-units/{$unitId}", [
            'credits' => 9,
        ])->assertOk()
            ->assertJsonPath('data.credits', 9);

        $this->deleteJson("/api/v1/course-units/{$unitId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('course_units', [
            'id' => $unitId,
        ]);
    }

    public function test_course_unit_requires_valid_type(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $program = AcademicProgram::factory()->create();

        $this->postJson('/api/v1/course-units', [
            'academic_program_id' => $program->id,
            'code' => 'UE102',
            'name' => 'Invalid Type',
            'semester_number' => 1,
            'credits' => 4,
            'type' => 'ELECTIVE',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_course_unit_requires_permission(): void
    {
        $this->seedPermissions($this->permissions);

        $this->actingAs(User::factory()->create());

        $this->getJson('/api/v1/course-units')
            ->assertStatus(403);
    }
}
