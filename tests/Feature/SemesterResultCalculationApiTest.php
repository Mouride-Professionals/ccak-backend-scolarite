<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\CalculateSemesterResultsJob;
use App\Enums\RegistrationStatus;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseUnit;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class SemesterResultCalculationApiTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    private User $admin;

    private User $faculty;

    private User $student;

    private AcademicYear $academicYear;

    private AcademicProgram $academicProgram;

    private CourseUnit $courseUnit;

    private array $permissions = [
        'semester_results.view',
        'semester_results.calculate',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'ADMIN', 'guard_name' => 'api']);
        Role::create(['name' => 'FACULTY', 'guard_name' => 'api']);
        Role::create(['name' => 'STUDENT', 'guard_name' => 'api']);

        // Create users
        $this->admin = User::create([
            'email' => 'admin@test.com',
            'password' => 'password',
            'keycloak_id' => (string) Str::uuid(),
            'is_active' => true,
        ]);
        $this->admin->assignRole('ADMIN');

        $this->faculty = User::create([
            'email' => 'faculty@test.com',
            'password' => 'password',
            'keycloak_id' => (string) Str::uuid(),
            'is_active' => true,
        ]);
        $this->faculty->assignRole('FACULTY');

        $this->student = User::create([
            'email' => 'student@test.com',
            'password' => 'password',
            'keycloak_id' => (string) Str::uuid(),
            'is_active' => true,
        ]);
        $this->student->assignRole('STUDENT');

        $this->seedPermissions($this->permissions);
        $this->admin->givePermissionTo($this->permissions);
        $this->faculty->givePermissionTo(['semester_results.calculate']);

        $this->academicYear = AcademicYear::create(['name' => '2023-2024']);

        $faculty = Faculty::factory()->create();
        $department = Department::factory()->create(['faculty_id' => $faculty->id]);
        $this->academicProgram = AcademicProgram::factory()->create(['department_id' => $department->id]);
        $this->courseUnit = CourseUnit::factory()->create([
            'academic_program_id' => $this->academicProgram->id,
            'semester_number' => 1,
            'credits' => 3,
            'type' => 'OBLIGATOIRE',
        ]);
    }

    private function getEnrollmentForStudent(Student $student, int $semester = 1): Enrollment
    {
        return Enrollment::firstOrCreate(
            [
                'student_id' => $student->id,
                'academic_program_id' => $this->academicProgram->id,
                'academic_year_id' => $this->academicYear->id,
            ],
            [
                'current_semester' => $semester,
                'status' => RegistrationStatus::VALIDATED->value,
                'enrollment_date' => now()->subMonths(1)->toDateString(),
                'registration_fee_paid' => 0,
                'is_scholarship_holder' => false,
            ]
        );
    }

    /**
     * @return array{course: Course, enrollment: CourseEnrollment}
     */
    private function createCourseWithEnrollment(
        Student $student,
        string $code,
        string $name,
        int $credits,
        int $coefficient,
        int $semester = 1
    ): array {
        $course = Course::factory()->create([
            'course_unit_id' => $this->courseUnit->id,
            'code' => $code,
            'name' => $name,
            'credits' => $credits,
            'coefficient' => $coefficient,
            'is_active' => true,
        ]);

        $enrollment = $this->getEnrollmentForStudent($student, $semester);

        $courseEnrollment = CourseEnrollment::create([
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'course_id' => $course->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => $semester,
            'status' => CourseEnrollment::STATUS_ENROLLED,
            'enrollment_date' => now()->subWeeks(2)->toDateString(),
        ]);

        return [
            'course' => $course,
            'enrollment' => $courseEnrollment,
        ];
    }

    #[Test]
    public function admin_can_trigger_semester_calculation_asynchronously()
    {
        Queue::fake();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/semester-results/calculate', [
                'academic_year_id' => $this->academicYear->id,
                'semester' => 1,
                'async' => true,
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'job_id',
                    'academic_year_id',
                    'semester',
                    'status',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'queued',
                    'academic_year_id' => $this->academicYear->id,
                    'semester' => 1,
                ],
            ]);

        Queue::assertPushed(CalculateSemesterResultsJob::class);
    }

    #[Test]
    public function admin_can_trigger_semester_calculation_synchronously()
    {
        // Create test student and course data
        $student = Student::factory()->create([
            'student_number' => 'STU001',
            'full_name' => 'Test Student',
        ]);

        ['course' => $course, 'enrollment' => $enrollment] = $this->createCourseWithEnrollment(
            $student,
            'CS101',
            'Computer Science',
            3,
            2
        );

        Grade::create([
            'course_enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'type' => 'EXAM',
            'score' => 15,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->admin->id,
            'status' => 'PUBLISHED',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/semester-results/calculate', [
                'academic_year_id' => $this->academicYear->id,
                'semester' => 1,
                'async' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'academic_year',
                    'semester',
                    'students_processed',
                    'results_created',
                    'errors',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'semester' => 1,
                    'students_processed' => 1,
                    'results_created' => 1,
                ],
            ]);

        // Verify semester result was created
        $this->assertDatabaseHas('semester_results', [
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 1,
            'decision' => 'VALIDATED',
        ]);
    }

    #[Test]
    public function non_admin_cannot_trigger_semester_calculation()
    {
        $response = $this->actingAs($this->faculty, 'api')
            ->postJson('/api/v1/semester-results/calculate', [
                'academic_year_id' => $this->academicYear->id,
                'semester' => 1,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only administrators are authorized to calculate semester results.',
                'errors' => ['authorization' => ['Admin role required']],
            ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_trigger_semester_calculation()
    {
        $response = $this->postJson('/api/v1/semester-results/calculate', [
            'academic_year_id' => $this->academicYear->id,
            'semester' => 1,
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function semester_calculation_validates_required_fields()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/semester-results/calculate', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['academic_year_id', 'semester']);
    }

    #[Test]
    public function semester_calculation_validates_academic_year_exists()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/semester-results/calculate', [
                'academic_year_id' => 'non-existent-id',
                'semester' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['academic_year_id']);
    }

    #[Test]
    public function semester_calculation_validates_semester_range()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/semester-results/calculate', [
                'academic_year_id' => $this->academicYear->id,
                'semester' => 3, // Invalid semester
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['semester']);
    }

    #[Test]
    public function admin_can_get_semester_statistics()
    {
        // Create test semester result
        $student = Student::factory()->create([
            'student_number' => 'STU001',
            'full_name' => 'Test Student',
        ]);

        \App\Models\SemesterResult::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 1,
            'total_credits_enrolled' => 5,
            'total_credits_earned' => 5,
            'semester_average' => 15.0,
            'semester_gpa' => 3.3,
            'decision' => 'VALIDATED',
            'calculated_by' => $this->admin->id,
            'calculated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/semester-results/statistics?'.http_build_query([
                'academic_year_id' => $this->academicYear->id,
                'semester' => 1,
            ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_students',
                    'decisions',
                    'success_rate',
                    'average_gpa',
                    'average_semester_average',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_students' => 1,
                    'decisions' => [
                        'VALIDATED' => 1,
                        'COMPENSATION' => 0,
                        'FAILED' => 0,
                        'RESIT_REQUIRED' => 0,
                    ],
                    'success_rate' => 100.0,
                ],
            ]);
    }

    #[Test]
    public function admin_can_recalculate_specific_student()
    {
        $student = Student::factory()->create([
            'student_number' => 'STU001',
            'full_name' => 'Test Student',
        ]);

        ['course' => $course, 'enrollment' => $enrollment] = $this->createCourseWithEnrollment(
            $student,
            'CS101',
            'Computer Science',
            3,
            2
        );

        Grade::create([
            'course_enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'type' => 'EXAM',
            'score' => 12,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->admin->id,
            'status' => 'PUBLISHED',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/semester-results/recalculate/{$student->id}", [
                'academic_year_id' => $this->academicYear->id,
                'semester' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'student_id',
                    'academic_year_id',
                    'semester',
                    'decision',
                    'semester_average',
                    'semester_gpa',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'student_id' => $student->id,
                    'decision' => 'VALIDATED',
                ],
            ]);
    }

    #[Test]
    public function non_admin_cannot_recalculate_student()
    {
        $student = Student::factory()->create([
            'student_number' => 'STU001',
            'full_name' => 'Test Student',
        ]);

        $response = $this->actingAs($this->faculty, 'api')
            ->postJson("/api/v1/semester-results/recalculate/{$student->id}", [
                'academic_year_id' => $this->academicYear->id,
                'semester' => 1,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only administrators are authorized to recalculate student results.',
            ]);
    }

    #[Test]
    public function recalculate_returns_error_for_non_existent_student()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/semester-results/recalculate/non-existent-id', [
                'academic_year_id' => $this->academicYear->id,
                'semester' => 1,
            ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function statistics_validates_required_parameters()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/semester-results/statistics');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['academic_year_id', 'semester']);
    }

    #[Test]
    public function statistics_returns_empty_data_for_no_results()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/semester-results/statistics?'.http_build_query([
                'academic_year_id' => $this->academicYear->id,
                'semester' => 1,
            ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_students' => 0,
                    'decisions' => [],
                    'average_gpa' => 0,
                    'average_semester_average' => 0,
                ],
            ]);
    }

    #[Test]
    public function async_parameter_defaults_to_true()
    {
        Queue::fake();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/semester-results/calculate', [
                'academic_year_id' => $this->academicYear->id,
                'semester' => 1,
                // async parameter not provided, should default to true
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'data' => ['status' => 'queued'],
            ]);

        Queue::assertPushed(CalculateSemesterResultsJob::class);
    }
}
