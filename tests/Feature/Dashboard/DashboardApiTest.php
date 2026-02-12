<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\DeliberationResult;
use App\Models\DeliberationSession;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\FacultyMember;
use App\Models\Student;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Authenticate::class);
    }

    public function test_dashboard_contract_endpoints_return_expected_shapes(): void
    {
        $faculty = Faculty::factory()->create();
        $department = Department::factory()->create(['faculty_id' => $faculty->id]);

        $licenceProgram = AcademicProgram::factory()->create([
            'department_id' => $department->id,
            'level' => AcademicProgram::LEVELS[0],
            'name' => 'Licence Mathematiques',
        ]);

        $masterProgram = AcademicProgram::factory()->create([
            'department_id' => $department->id,
            'level' => AcademicProgram::LEVELS[1],
            'name' => 'Master Informatique',
        ]);

        $academicYear = AcademicYear::factory()->create();

        $student1 = Student::factory()->active()->create();
        $student2 = Student::factory()->active()->create();
        $student3 = Student::factory()->active()->create();

        Enrollment::factory()->create([
            'student_id' => $student1->id,
            'academic_program_id' => $licenceProgram->id,
            'academic_year_id' => $academicYear->id,
            'current_semester' => 2,
            'status' => Enrollment::STATUS_PENDING,
            'enrollment_date' => now()->toDateString(),
            'created_at' => now()->subDay(),
        ]);

        Enrollment::factory()->create([
            'student_id' => $student2->id,
            'academic_program_id' => $licenceProgram->id,
            'academic_year_id' => $academicYear->id,
            'current_semester' => 4,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrollment_date' => now()->toDateString(),
            'created_at' => now()->subHours(8),
        ]);

        Enrollment::factory()->create([
            'student_id' => $student3->id,
            'academic_program_id' => $masterProgram->id,
            'academic_year_id' => $academicYear->id,
            'current_semester' => 1,
            'status' => Enrollment::STATUS_COMPLETED,
            'enrollment_date' => now()->toDateString(),
            'created_at' => now()->subHours(4),
        ]);

        $president = FacultyMember::factory()->create(['department_id' => $department->id]);

        $session = DeliberationSession::factory()->create([
            'academic_program_id' => $licenceProgram->id,
            'academic_year_id' => $academicYear->id,
            'semester' => 2,
            'status' => DeliberationSession::STATUS_COMPLETED,
            'presided_by' => $president->id,
            'session_date' => now()->toDateString(),
            'created_at' => now()->subHours(2),
        ]);

        DeliberationResult::factory()->create([
            'deliberation_session_id' => $session->id,
            'student_id' => $student1->id,
            'decision' => DeliberationResult::DECISION_ADMITTED,
        ]);

        DeliberationResult::factory()->create([
            'deliberation_session_id' => $session->id,
            'student_id' => $student2->id,
            'decision' => DeliberationResult::DECISION_FAILED,
        ]);

        $query = http_build_query([
            'academic_year_id' => $academicYear->id,
            'department_id' => $department->id,
            'faculty_id' => $faculty->id,
        ]);

        $this->getJson("/api/v1/dashboard/overview?{$query}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_students', 3)
            ->assertJsonPath('data.total_enrollments', 3)
            ->assertJsonPath('data.total_deliberations', 1)
            ->assertJsonPath('data.pending_items', 1)
            ->assertJsonStructure([
                'meta' => ['generated_at', 'filters'],
            ]);

        $this->getJson("/api/v1/dashboard/students-by-level?{$query}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.niveau', 'L1')
            ->assertJsonPath('data.0.licence', 1)
            ->assertJsonPath('data.1.niveau', 'L2')
            ->assertJsonPath('data.1.licence', 1)
            ->assertJsonPath('data.3.niveau', 'M1')
            ->assertJsonPath('data.3.master', 1);

        $this->getJson("/api/v1/dashboard/enrollments-trend?{$query}&period=6m")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(6, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['month', 'value'],
                ],
            ]);

        $this->getJson("/api/v1/dashboard/validation-rate?{$query}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.validated_count', 1)
            ->assertJsonPath('data.failed_count', 1)
            ->assertJsonPath('data.validated_percent', 50)
            ->assertJsonPath('data.failed_percent', 50);

        $this->getJson("/api/v1/dashboard/recent-activities?{$query}&limit=3")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'action', 'context', 'occurred_at', 'status'],
                ],
            ]);

        $this->getJson("/api/v1/enrollments/dashboard?{$query}&period=6m")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kpis.total_enrollments', 3)
            ->assertJsonPath('data.kpis.pending_enrollments', 1)
            ->assertJsonPath('data.kpis.completed_enrollments', 1)
            ->assertJsonPath('data.kpis.active_enrollments', 1)
            ->assertJsonStructure([
                'data' => [
                    'kpis' => [
                        'total_enrollments',
                        'active_enrollments',
                        'pending_enrollments',
                        'completed_enrollments',
                        'withdrawn_enrollments',
                    ],
                    'trend' => [
                        '*' => ['month', 'count'],
                    ],
                    'program_distribution' => [
                        '*' => ['name', 'count'],
                    ],
                    'recent_enrollments',
                ],
            ]);
    }

    public function test_dashboard_endpoints_validate_uuid_filters_and_return_json_errors(): void
    {
        $response = $this->getJson('/api/v1/dashboard/overview?academic_year_id=not-a-uuid');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'message',
                'errors' => ['academic_year_id'],
            ]);
    }
}
