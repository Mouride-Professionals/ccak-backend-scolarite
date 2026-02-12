<?php

declare(strict_types=1);

namespace Tests\Feature\Academic;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseUnit;
use App\Models\Evaluation;
use App\Models\FacultyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class EvaluationApiTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    private array $permissions = [
        'evaluations.view',
        'evaluations.create',
        'evaluations.update',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_can_list_evaluations_with_pagination(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $courseUnit = CourseUnit::factory()->create();
        $course = Course::factory()->create(['course_unit_id' => $courseUnit->id]);
        $faculty = FacultyMember::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        Evaluation::create([
            'course_id' => $course->id,
            'faculty_member_id' => $faculty->id,
            'academic_year_id' => $academicYear->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'question_template' => [['key' => 'q1', 'label' => 'Question 1']],
            'is_published' => true,
            'response_deadline' => now()->addDays(10),
        ]);

        Evaluation::create([
            'course_id' => $course->id,
            'faculty_member_id' => $faculty->id,
            'academic_year_id' => $academicYear->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'question_template' => [['key' => 'q2', 'label' => 'Question 2']],
            'is_published' => false,
            'response_deadline' => now()->addDays(12),
        ]);

        $response = $this->getJson('/api/v1/evaluations?page=1&per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_evaluations_require_permission(): void
    {
        $this->seedPermissions($this->permissions);
        $this->actingAs(User::factory()->create());

        $this->getJson('/api/v1/evaluations')->assertStatus(403);
    }
}
