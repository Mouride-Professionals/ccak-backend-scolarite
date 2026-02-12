<?php

declare(strict_types=1);

namespace Tests\Unit;

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
use App\Models\Enums\DecisionType;
use App\Services\SemesterResultCalculationService;
use App\Services\GradeCalculationService;
use App\Repositories\SemesterResultRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SemesterResultCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private SemesterResultCalculationService $service;
    private GradeCalculationService $gradeService;
    private SemesterResultRepository $repository;
    private User $admin;
    private Student $student;
    private AcademicYear $academicYear;
    private Course $course1;
    private Course $course2;
    private Enrollment $academicEnrollment;
    private CourseUnit $courseUnit;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'ADMIN', 'guard_name' => 'api']);
        Role::create(['name' => 'FACULTY', 'guard_name' => 'api']);

        // Initialize services
        $this->gradeService = new GradeCalculationService();
        $this->repository = new SemesterResultRepository();
        $this->service = new SemesterResultCalculationService($this->gradeService, $this->repository);

        // Create test admin user
        $this->admin = User::create([
            'email' => 'admin@test.com',
            'password' => 'password',
            'keycloak_id' => (string) Str::uuid(),
            'is_active' => true,
        ]);
        $this->admin->assignRole('ADMIN');

        // Create test data
        $this->student = Student::factory()->create([
            'student_number' => 'STU001',
            'full_name' => 'Test Student',
        ]);

        $faculty = Faculty::factory()->create();
        $department = Department::factory()->create(['faculty_id' => $faculty->id]);
        $program = AcademicProgram::factory()->create(['department_id' => $department->id]);

        $this->courseUnit = CourseUnit::factory()->create([
            'academic_program_id' => $program->id,
            'semester_number' => 1,
            'credits' => 3,
            'type' => 'OBLIGATOIRE',
        ]);

        $this->academicYear = AcademicYear::factory()->create(['name' => '2023-2024']);

        $this->academicEnrollment = Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'academic_program_id' => $program->id,
            'academic_year_id' => $this->academicYear->id,
            'current_semester' => 1,
            'status' => 'ACTIVE',
            'enrollment_date' => now()->subMonths(1)->toDateString(),
            'registration_fee_paid' => 0,
            'is_scholarship' => false,
        ]);

        $this->course1 = Course::factory()->create([
            'course_unit_id' => $this->courseUnit->id,
            'code' => 'CS101',
            'name' => 'Introduction to Computer Science',
            'credits' => 3,
            'coefficient' => 2,
            'is_active' => true,
        ]);

        $this->course2 = Course::factory()->create([
            'course_unit_id' => $this->courseUnit->id,
            'code' => 'MATH101',
            'name' => 'Mathematics',
            'credits' => 4,
            'coefficient' => 3,
            'is_active' => true,
        ]);
    }

    private function createCourseEnrollment(Course $course, int $semester = 1): CourseEnrollment
    {
        return CourseEnrollment::create([
            'student_id' => $this->student->id,
            'enrollment_id' => $this->academicEnrollment->id,
            'course_id' => $course->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => $semester,
            'status' => CourseEnrollment::STATUS_ENROLLED,
            'enrollment_date' => now()->subWeeks(2)->toDateString(),
        ]);
    }
    #[Test]
    public function it_calculates_semester_results_successfully()
    {
        // Create course enrollments
        $enrollment1 = $this->createCourseEnrollment($this->course1);
        $enrollment2 = $this->createCourseEnrollment($this->course2);

        // Create passing grades
        Grade::create([
            'course_enrollment_id' => $enrollment1->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'type' => 'EXAM',
            'score' => 15,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->admin->id,
            'status' => 'PUBLISHED',
        ]);

        Grade::create([
            'course_enrollment_id' => $enrollment2->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course2->id,
            'type' => 'EXAM',
            'score' => 12,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->admin->id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->calculateSemesterResults(
            $this->academicYear->id,
            1,
            $this->admin
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['data']['students_processed']);
        $this->assertEquals(1, $result['data']['results_created']);
        $this->assertEmpty($result['data']['errors']);

        // Check that semester result was created
        $semesterResult = \App\Models\SemesterResult::where('student_id', $this->student->id)
            ->where('academic_year_id', $this->academicYear->id)
            ->where('semester', 1)
            ->first();

        $this->assertNotNull($semesterResult);
        $this->assertEquals(DecisionType::VALIDATED, $semesterResult->decision);
        $this->assertEquals(5, $semesterResult->total_credits_enrolled); // 2 + 3
        $this->assertEquals(5, $semesterResult->total_credits_earned);    // Both courses passed
    }
    #[Test]
    public function it_calculates_student_result_with_compensation()
    {
        // Create course enrollments
        $enrollment1 = $this->createCourseEnrollment($this->course1);
        $enrollment2 = $this->createCourseEnrollment($this->course2);

        // Create grades: one passing, one compensable
        Grade::create([
            'course_enrollment_id' => $enrollment1->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'type' => 'EXAM',
            'score' => 12,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->admin->id,
            'status' => 'PUBLISHED',
        ]);

        Grade::create([
            'course_enrollment_id' => $enrollment2->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course2->id,
            'type' => 'EXAM',
            'score' => 9,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->admin->id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->calculateStudentSemesterResult(
            $this->student,
            $this->academicYear->id,
            1,
            $this->admin
        );

        $this->assertNotNull($result);
        $this->assertEquals(DecisionType::COMPENSATION, $result->decision);
        $this->assertEquals(5, $result->total_credits_enrolled);
        $this->assertEquals(5, $result->total_credits_earned); // Both courses credited through compensation
    }
    #[Test]
    public function it_determines_failed_decision_correctly()
    {
        $enrollment1 = $this->createCourseEnrollment($this->course1);
        $enrollment2 = $this->createCourseEnrollment($this->course2);

        // Create failing grades
        Grade::create([
            'course_enrollment_id' => $enrollment1->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'type' => 'EXAM',
            'score' => 7,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->admin->id,
            'status' => 'PUBLISHED',
        ]);

        Grade::create([
            'course_enrollment_id' => $enrollment2->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course2->id,
            'type' => 'EXAM',
            'score' => 6,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->admin->id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->calculateStudentSemesterResult(
            $this->student,
            $this->academicYear->id,
            1,
            $this->admin
        );

        $this->assertNotNull($result);
        $this->assertEquals(DecisionType::FAILED, $result->decision);
        $this->assertEquals(5, $result->total_credits_enrolled);
        $this->assertEquals(0, $result->total_credits_earned); // No credits earned
    }
    #[Test]
    public function it_determines_resit_required_correctly()
    {
        $enrollment1 = $this->createCourseEnrollment($this->course1);
        $enrollment2 = $this->createCourseEnrollment($this->course2);

        // Create grades that lead to resit (failed but decent average)
        Grade::create([
            'course_enrollment_id' => $enrollment1->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'type' => 'EXAM',
            'score' => 10,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->admin->id,
            'status' => 'PUBLISHED',
        ]);

        Grade::create([
            'course_enrollment_id' => $enrollment2->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course2->id,
            'type' => 'EXAM',
            'score' => 7,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->admin->id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->calculateStudentSemesterResult(
            $this->student,
            $this->academicYear->id,
            1,
            $this->admin
        );

        $this->assertNotNull($result);
        $this->assertEquals(DecisionType::RESIT_REQUIRED, $result->decision);
    }
    #[Test]
    public function it_updates_existing_semester_result()
    {
        $enrollment = $this->createCourseEnrollment($this->course1);

        Grade::create([
            'course_enrollment_id' => $enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'type' => 'EXAM',
            'score' => 12,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->admin->id,
            'status' => 'PUBLISHED',
        ]);

        // Create initial result
        $initialResult = $this->service->calculateStudentSemesterResult(
            $this->student,
            $this->academicYear->id,
            1,
            $this->admin
        );

        $this->assertNotNull($initialResult);
        $initialId = $initialResult->id;

        // Recalculate (should update, not create new)
        $updatedResult = $this->service->calculateStudentSemesterResult(
            $this->student,
            $this->academicYear->id,
            1,
            $this->admin
        );

        $this->assertEquals($initialId, $updatedResult->id);
    }
    #[Test]
    public function it_calculates_semester_statistics_correctly()
    {
        // Create multiple students with different results
        $student2 = Student::factory()->create([
            'student_number' => 'STU002',
            'full_name' => 'Test Student 2',
        ]);

        // Create semester results directly
        \App\Models\SemesterResult::create([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 1,
            'total_credits_enrolled' => 5,
            'total_credits_earned' => 5,
            'semester_average' => 15.0,
            'semester_gpa' => 3.3,
            'decision' => DecisionType::VALIDATED,
            'calculated_by' => $this->admin->id,
            'calculated_at' => now(),
        ]);

        \App\Models\SemesterResult::create([
            'student_id' => $student2->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 1,
            'total_credits_enrolled' => 5,
            'total_credits_earned' => 3,
            'semester_average' => 9.5,
            'semester_gpa' => 2.0,
            'decision' => DecisionType::COMPENSATION,
            'calculated_by' => $this->admin->id,
            'calculated_at' => now(),
        ]);

        $statistics = $this->service->getSemesterStatistics($this->academicYear->id, 1);

        $this->assertEquals(2, $statistics['total_students']);
        $this->assertEquals(1, $statistics['decisions']['VALIDATED']);
        $this->assertEquals(1, $statistics['decisions']['COMPENSATION']);
        $this->assertEquals(0, $statistics['decisions']['FAILED']);
        $this->assertEquals(0, $statistics['decisions']['RESIT_REQUIRED']);
        $this->assertEquals(100.0, $statistics['success_rate']); // Both passed
        $this->assertEquals(2.65, $statistics['average_gpa']); // (3.3 + 2.0) / 2
        $this->assertEquals(12.25, $statistics['average_semester_average']); // (15.0 + 9.5) / 2
    }
    #[Test]
    public function it_handles_no_students_case()
    {
        $result = $this->service->calculateSemesterResults(
            $this->academicYear->id,
            1,
            $this->admin
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No students found', $result['message']);
        $this->assertEquals(0, $result['data']['students_processed']);
        $this->assertEquals(0, $result['data']['results_created']);
    }
    #[Test]
    public function it_returns_empty_statistics_for_no_results()
    {
        $statistics = $this->service->getSemesterStatistics($this->academicYear->id, 1);

        $this->assertEquals(0, $statistics['total_students']);
        $this->assertEquals([], $statistics['decisions']);
        $this->assertEquals(0, $statistics['average_gpa']);
        $this->assertEquals(0, $statistics['average_semester_average']);
    }
    #[Test]
    public function it_calculates_credits_earned_correctly_for_compensation()
    {
        // This tests the private method through public interface
        $enrollment1 = $this->createCourseEnrollment($this->course1);
        $enrollment2 = $this->createCourseEnrollment($this->course2);

        // One passing, one compensable
        Grade::create([
            'course_enrollment_id' => $enrollment1->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'type' => 'EXAM',
            'score' => 15,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->admin->id,
            'status' => 'PUBLISHED',
        ]);

        Grade::create([
            'course_enrollment_id' => $enrollment2->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course2->id,
            'type' => 'EXAM',
            'score' => 9,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->admin->id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->calculateStudentSemesterResult(
            $this->student,
            $this->academicYear->id,
            1,
            $this->admin
        );

        // Both courses should award credits through compensation
        $this->assertEquals(5, $result->total_credits_enrolled);
        $this->assertEquals(5, $result->total_credits_earned);
    }
}
