<?php

namespace Tests\Feature\Academic;

use App\Models\Course;
use App\Models\CourseUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class CourseApiTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    private array $permissions = [
        'courses.view',
        'courses.create',
        'courses.update',
        'courses.delete',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_can_crud_courses(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $courseUnit = CourseUnit::factory()->create();
        $prerequisite = Course::factory()->create();

        $payload = [
            'course_unit_id' => $courseUnit->id,
            'code' => 'CS101',
            'name' => 'Intro to CS',
            'credits' => 5,
            'hours_lecture' => 20,
            'hours_td' => 10,
            'hours_tp' => 0,
            'coefficient' => 1.5,
            'prerequisites' => [$prerequisite->id],
            'is_active' => true,
        ];

        $createResponse = $this->postJson('/api/v1/courses', $payload);
        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Intro to CS')
            ->assertJsonPath('data.code', 'CS101')
            ->assertJsonPath('data.course_unit_id', $courseUnit->id)
            ->assertJsonPath('data.prerequisites.0', $prerequisite->id);

        $courseId = $createResponse->json('data.id');

        $this->assertDatabaseHas('courses', [
            'id' => $courseId,
            'code' => 'CS101',
        ]);

        $this->getJson('/api/v1/courses')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/v1/courses/{$courseId}")
            ->assertOk()
            ->assertJsonPath('data.id', $courseId);

        $this->putJson("/api/v1/courses/{$courseId}", [
            'name' => 'Intro to Computing',
            'prerequisites' => [],
        ])->assertOk()
            ->assertJsonPath('data.name', 'Intro to Computing');

        $this->deleteJson("/api/v1/courses/{$courseId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('courses', [
            'id' => $courseId,
        ]);
    }

    public function test_course_requires_course_unit_and_valid_prerequisites(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $this->postJson('/api/v1/courses', [
            'code' => 'CS201',
            'name' => 'Data Structures',
            'credits' => 4,
            'prerequisites' => ['not-a-uuid'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['course_unit_id', 'prerequisites.0']);
    }

    public function test_course_requires_permission(): void
    {
        $this->seedPermissions($this->permissions);

        $this->actingAs(User::factory()->create());

        $this->getJson('/api/v1/courses')
            ->assertStatus(403);
    }
}
