<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Grade;
use App\Models\Student;
use App\Models\User;
use App\Jobs\CalculateSemesterResultsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SemesterResultCalculationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $faculty;
    private User $student;
    private AcademicYear $academicYear;

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
            'keycloak_id' => 'admin-123',
            'is_active' => true,
        ]);
        $this->admin->assignRole('ADMIN');

        $this->faculty = User::create([
            'email' => 'faculty@test.com',
            'password' => 'password',
            'keycloak_id' => 'faculty-123',
            'is_active' => true,
        ]);
        $this->faculty->assignRole('FACULTY');

        $this->student = User::create([
            'email' => 'student@test.com',
            'password' => 'password',
            'keycloak_id' => 'student-123',
            'is_active' => true,
        ]);
        $this->student->assignRole('STUDENT');

        $this->academicYear = AcademicYear::create(['name' => '2023-2024']);
    }

    /** @test */
    public function admin_can_trigger_semester_calculation_asynchronously()
    {
        Queue::fake();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/semester-results/calculate', [
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
                    'status'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'queued',
                    'academic_year_id' => $this->academicYear->id,
                    'semester' => 1,
                ]
            ]);

        Queue::assertPushed(CalculateSemesterResultsJob::class);
    }

    /** @test */
    public function admin_can_trigger_semester_calculation_synchronously()
    {
        // Create test student and course data
        $student = Student::create([
            'student_number' => 'STU001',
            'full_name' => 'Test Student',
        ]);

        $course = Course::create([
            'code' => 'CS101',
            'name' => 'Computer Science',
            'credits' => 3,
            'coefficient' => 2,
            'is_active' => true,
        ]);

        $enrollment = CourseEnrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

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
            ->postJson('/api/semester-results/calculate', [
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
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'semester' => 1,
                    'students_processed' => 1,
                    'results_created' => 1,
                ]
            ]);

        // Verify semester result was created
        $this->assertDatabaseHas('semester_results', [
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 1,
            'decision' => 'VALIDATED',
        ]);
    }

    /** @test */
    public function non_admin_cannot_trigger_semester_calculation()
    {
        $response = $this->actingAs($this->faculty, 'api')
            ->postJson('/api/semester-results/calculate', [
                'academic_year_id' => $this->academicYear->id,
                'semester' => 1,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only administrators are authorized to calculate semester results.',
                'errors' => ['authorization' => ['Admin role required']]
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_trigger_semester_calculation()
    {
        $response = $this->postJson('/api/semester-results/calculate', [
            'academic_year_id' => $this->academicYear->id,
            'semester' => 1,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function semester_calculation_validates_required_fields()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/semester-results/calculate', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['academic_year_id', 'semester']);
    }

    /** @test */
    public function semester_calculation_validates_academic_year_exists()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/semester-results/calculate', [
                'academic_year_id' => 'non-existent-id',
                'semester' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['academic_year_id']);
    }

    /** @test */
    public function semester_calculation_validates_semester_range()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/semester-results/calculate', [
                'academic_year_id' => $this->academicYear->id,
                'semester' => 3, // Invalid semester
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['semester']);
    }

    /** @test */
    public function admin_can_get_semester_statistics()
    {
        // Create test semester result
        $student = Student::create([
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
            ->getJson('/api/semester-results/statistics?' . http_build_query([
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
                ]
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
                ]
            ]);
    }

    /** @test */
    public function admin_can_recalculate_specific_student()
    {
        $student = Student::create([
            'student_number' => 'STU001',
            'full_name' => 'Test Student',
        ]);

        $course = Course::create([
            'code' => 'CS101',
            'name' => 'Computer Science',
            'credits' => 3,
            'coefficient' => 2,
            'is_active' => true,
        ]);

        $enrollment = CourseEnrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

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
            ->postJson("/api/semester-results/recalculate/{$student->id}", [
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
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'student_id' => $student->id,
                    'decision' => 'VALIDATED',
                ]
            ]);
    }

    /** @test */
    public function non_admin_cannot_recalculate_student()
    {
        $student = Student::create([
            'student_number' => 'STU001',
            'full_name' => 'Test Student',
        ]);

        $response = $this->actingAs($this->faculty, 'api')
            ->postJson("/api/semester-results/recalculate/{$student->id}", [
                'academic_year_id' => $this->academicYear->id,
                'semester' => 1,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only administrators are authorized to recalculate student results.',
            ]);
    }

    /** @test */
    public function recalculate_returns_error_for_non_existent_student()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/semester-results/recalculate/non-existent-id", [
                'academic_year_id' => $this->academicYear->id,
                'semester' => 1,
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function statistics_validates_required_parameters()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/semester-results/statistics');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['academic_year_id', 'semester']);
    }

    /** @test */
    public function statistics_returns_empty_data_for_no_results()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/semester-results/statistics?' . http_build_query([
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
                ]
            ]);
    }

    /** @test */
    public function async_parameter_defaults_to_true()
    {
        Queue::fake();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/semester-results/calculate', [
                'academic_year_id' => $this->academicYear->id,
                'semester' => 1,
                // async parameter not provided, should default to true
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'data' => ['status' => 'queued']
            ]);

        Queue::assertPushed(CalculateSemesterResultsJob::class);
    }
}
