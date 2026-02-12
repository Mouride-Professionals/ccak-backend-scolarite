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
use App\Services\GradeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GradeCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private GradeCalculationService $service;
    private Student $student;
    private Course $course;
    private CourseEnrollment $enrollment;
    private AcademicYear $academicYear;
    private Enrollment $academicEnrollment;
    private CourseUnit $courseUnit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GradeCalculationService();

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

        $this->academicYear = AcademicYear::factory()->create(['name' => '2024-2025']);

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

        $this->course = Course::factory()->create([
            'course_unit_id' => $this->courseUnit->id,
            'code' => 'CS101',
            'name' => 'Introduction to Computer Science',
            'credits' => 3,
            'coefficient' => 2,
            'is_active' => true,
        ]);

        $this->enrollment = CourseEnrollment::create([
            'student_id' => $this->student->id,
            'enrollment_id' => $this->academicEnrollment->id,
            'course_id' => $this->course->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 1,
            'status' => CourseEnrollment::STATUS_ENROLLED,
            'enrollment_date' => now()->subWeeks(2)->toDateString(),
        ]);
    }

    /**
     * @return array{course: Course, enrollment: CourseEnrollment}
     */
    private function createCourseWithEnrollment(
        string $code,
        string $name,
        int $credits,
        int $coefficient
    ): array {
        $course = Course::factory()->create([
            'course_unit_id' => $this->courseUnit->id,
            'code' => $code,
            'name' => $name,
            'credits' => $credits,
            'coefficient' => $coefficient,
            'is_active' => true,
        ]);

        $enrollment = CourseEnrollment::create([
            'student_id' => $this->student->id,
            'enrollment_id' => $this->academicEnrollment->id,
            'course_id' => $course->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 1,
            'status' => CourseEnrollment::STATUS_ENROLLED,
            'enrollment_date' => now()->subWeeks(2)->toDateString(),
        ]);

        return [
            'course' => $course,
            'enrollment' => $enrollment,
        ];
    }

    #[Test]
    public function it_calculates_course_average_with_weighted_grades()
    {
        // Create grades with different weights
        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'CC',
            'score' => 15,
            'max_score' => 20,
            'weight' => 0.3,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'EXAM',
            'score' => 16,
            'max_score' => 20,
            'weight' => 0.5,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'TP',
            'score' => 18,
            'max_score' => 20,
            'weight' => 0.2,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->calculateCourseAverage($this->enrollment->id);

        // Expected: (15 * 0.3) + (16 * 0.5) + (18 * 0.2) = 4.5 + 8 + 3.6 = 16.1
        $this->assertEquals(16.1, $result['average']);
        $this->assertEquals(1.0, $result['total_weight']);
        $this->assertTrue($result['is_complete']);
        $this->assertCount(3, $result['grades_breakdown']);
    }

    #[Test]
    public function it_normalizes_scores_to_20_scale()
    {
        // Create a grade with max_score different from 20
        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'EXAM',
            'score' => 80,
            'max_score' => 100,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->calculateCourseAverage($this->enrollment->id);

        // Expected: (80/100) * 20 = 16
        $this->assertEquals(16.0, $result['average']);
    }

    #[Test]
    public function it_returns_null_when_no_published_grades_exist()
    {
        // Create a draft grade
        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'CC',
            'score' => 15,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'DRAFT',
        ]);

        $result = $this->service->calculateCourseAverage($this->enrollment->id);

        $this->assertNull($result['average']);
        $this->assertFalse($result['is_complete']);
    }

    #[Test]
    public function it_marks_incomplete_when_weights_dont_sum_to_one()
    {
        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'CC',
            'score' => 15,
            'max_score' => 20,
            'weight' => 0.3,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->calculateCourseAverage($this->enrollment->id);

        $this->assertFalse($result['is_complete']);
        $this->assertEquals(0.3, $result['total_weight']);
    }

    #[Test]
    public function it_calculates_semester_average_with_course_coefficients()
    {
        // Create second course
        ['course' => $course2, 'enrollment' => $enrollment2] = $this->createCourseWithEnrollment(
            'CS102',
            'Data Structures',
            3,
            3
        );

        // Course 1: Average 15/20, Coefficient 2
        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'EXAM',
            'score' => 15,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        // Course 2: Average 12/20, Coefficient 3
        Grade::create([
            'course_enrollment_id' => $enrollment2->id,
            'student_id' => $this->student->id,
            'course_id' => $course2->id,
            'type' => 'EXAM',
            'score' => 12,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->calculateSemesterAverage(
            $this->student->id,
            [$this->course->id, $course2->id]
        );

        // Expected: (15 * 2 + 12 * 3) / (2 + 3) = (30 + 36) / 5 = 13.2
        $this->assertEquals(13.2, $result['semester_average']);
        $this->assertEquals(5, $result['total_credits']);
        $this->assertCount(2, $result['courses']);
    }

    #[Test]
    public function it_converts_grades_to_gpa_scale_correctly()
    {
        $testCases = [
            ['grade' => 19, 'expected_gpa' => 4.0],
            ['grade' => 17, 'expected_gpa' => 3.7],
            ['grade' => 15, 'expected_gpa' => 3.3],
            ['grade' => 13, 'expected_gpa' => 3.0],
            ['grade' => 11, 'expected_gpa' => 2.7],
            ['grade' => 9, 'expected_gpa' => 2.0],
            ['grade' => 7, 'expected_gpa' => 1.0],
            ['grade' => 5, 'expected_gpa' => 0.0],
        ];

        foreach ($testCases as $case) {
            $gpa = $this->service->convertToGpaScale($case['grade']);
            $this->assertEquals($case['expected_gpa'], $gpa);
        }
    }

    #[Test]
    public function it_gets_letter_grades_correctly()
    {
        $testCases = [
            ['grade' => 19, 'expected_letter' => 'A+'],
            ['grade' => 17, 'expected_letter' => 'A'],
            ['grade' => 15, 'expected_letter' => 'B+'],
            ['grade' => 13, 'expected_letter' => 'B'],
            ['grade' => 11, 'expected_letter' => 'C+'],
            ['grade' => 9, 'expected_letter' => 'C'],
            ['grade' => 7, 'expected_letter' => 'D'],
            ['grade' => 5, 'expected_letter' => 'F'],
        ];

        foreach ($testCases as $case) {
            $letter = $this->service->getLetterGrade($case['grade']);
            $this->assertEquals($case['expected_letter'], $letter);
        }
    }

    #[Test]
    public function it_calculates_semester_gpa_with_credits()
    {
        // Create second course
        ['course' => $course2, 'enrollment' => $enrollment2] = $this->createCourseWithEnrollment(
            'CS102',
            'Data Structures',
            4,
            3
        );

        // Course 1: Average 15/20 (GPA 3.3), Coefficient 2
        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'EXAM',
            'score' => 15,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        // Course 2: Average 12/20 (GPA 3.0), Coefficient 3
        Grade::create([
            'course_enrollment_id' => $enrollment2->id,
            'student_id' => $this->student->id,
            'course_id' => $course2->id,
            'type' => 'EXAM',
            'score' => 12,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->calculateGPA(
            $this->student->id,
            [$this->course->id, $course2->id]
        );

        // Expected GPA: (3.3 * 2 + 3.0 * 3) / (2 + 3) = (6.6 + 9) / 5 = 3.12
        $this->assertEquals(3.12, $result['gpa']);
        $this->assertEquals(5, $result['total_credits']);
        $this->assertCount(2, $result['courses_gpa']);

        // Check individual course data
        $courseGpaData = collect($result['courses_gpa'])->keyBy('course_code');
        $this->assertEquals(3.3, $courseGpaData['CS101']['gpa']);
        $this->assertEquals('B+', $courseGpaData['CS101']['letter_grade']);
        $this->assertEquals(3.0, $courseGpaData['CS102']['gpa']);
        $this->assertEquals('B', $courseGpaData['CS102']['letter_grade']);
    }

    #[Test]
    public function it_calculates_cumulative_gpa_across_semesters()
    {
        // Setup courses for multiple semesters
        ['course' => $course2, 'enrollment' => $enrollment2] = $this->createCourseWithEnrollment(
            'CS102',
            'Math',
            3,
            2
        );

        ['course' => $course3, 'enrollment' => $enrollment3] = $this->createCourseWithEnrollment(
            'CS103',
            'Physics',
            4,
            3
        );

        // Semester 1: Course 1 (15/20, coeff 2) and Course 2 (12/20, coeff 2)
        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'EXAM',
            'score' => 15,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        Grade::create([
            'course_enrollment_id' => $enrollment2->id,
            'student_id' => $this->student->id,
            'course_id' => $course2->id,
            'type' => 'EXAM',
            'score' => 12,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        // Semester 2: Course 3 (16/20, coeff 3)
        Grade::create([
            'course_enrollment_id' => $enrollment3->id,
            'student_id' => $this->student->id,
            'course_id' => $course3->id,
            'type' => 'EXAM',
            'score' => 16,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->calculateCumulativeGPA(
            $this->student->id,
            [
                [$this->course->id, $course2->id], // Semester 1
                [$course3->id], // Semester 2
            ]
        );

        // Semester 1 GPA: (3.3*2 + 3.0*2) / 4 = 3.15
        // Semester 2 GPA: (3.7*3) / 3 = 3.7
        // Cumulative: (3.3*2 + 3.0*2 + 3.7*3) / 7 = 3.386
        $this->assertEqualsWithDelta(3.386, $result['cumulative_gpa'], 0.01);
        $this->assertEquals(7, $result['total_credits']);
        $this->assertCount(2, $result['semesters']);
    }

    #[Test]
    public function it_applies_compensation_rules_correctly()
    {
        // Create 3 courses
        ['course' => $course2, 'enrollment' => $enrollment2] = $this->createCourseWithEnrollment(
            'CS102',
            'Math',
            3,
            2
        );

        ['course' => $course3, 'enrollment' => $enrollment3] = $this->createCourseWithEnrollment(
            'CS103',
            'Physics',
            3,
            2
        );

        // Course 1: 14/20 (Pass)
        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'EXAM',
            'score' => 14,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        // Course 2: 9/20 (Compensable - between 8 and 10)
        Grade::create([
            'course_enrollment_id' => $enrollment2->id,
            'student_id' => $this->student->id,
            'course_id' => $course2->id,
            'type' => 'EXAM',
            'score' => 9,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        // Course 3: 7/20 (Not compensable - below 8)
        Grade::create([
            'course_enrollment_id' => $enrollment3->id,
            'student_id' => $this->student->id,
            'course_id' => $course3->id,
            'type' => 'EXAM',
            'score' => 7,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->applyCompensationRules(
            $this->student->id,
            [$this->course->id, $course2->id, $course3->id]
        );

        // Semester average: (14 + 9 + 7) / 3 = 10
        $this->assertEquals(10.0, $result['semester_average']);
        $this->assertTrue($result['can_compensate']);
        $this->assertCount(1, $result['compensated_courses']);
        $this->assertCount(1, $result['failed_courses']);

        // Course 2 should be compensated
        $this->assertEquals('CS102', $result['compensated_courses'][0]['course_code']);

        // Course 3 should fail
        $this->assertEquals('CS103', $result['failed_courses'][0]['course_code']);
    }

    #[Test]
    public function it_does_not_compensate_when_semester_average_is_below_10()
    {
        ['course' => $course2, 'enrollment' => $enrollment2] = $this->createCourseWithEnrollment(
            'CS102',
            'Math',
            3,
            1
        );

        // Course 1: 8/20
        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'EXAM',
            'score' => 8,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        // Course 2: 9/20
        Grade::create([
            'course_enrollment_id' => $enrollment2->id,
            'student_id' => $this->student->id,
            'course_id' => $course2->id,
            'type' => 'EXAM',
            'score' => 9,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->applyCompensationRules(
            $this->student->id,
            [$this->course->id, $course2->id]
        );

        // Semester average weighted by coefficient: (8*2 + 9*1) / 3 = 8.33 (below 10)
        $this->assertEquals(8.33, $result['semester_average']);
        $this->assertFalse($result['can_compensate']);
        $this->assertCount(0, $result['compensated_courses']);
        $this->assertCount(2, $result['failed_courses']);
    }

    #[Test]
    public function it_determines_pass_fail_for_single_course()
    {
        // Passing grade
        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'EXAM',
            'score' => 12,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->determinePassFail(
            $this->student->id,
            $this->enrollment->id
        );

        $this->assertEquals('course', $result['type']);
        $this->assertEquals('PASSED', $result['status']);
        $this->assertTrue($result['passed']);
        $this->assertEquals(12.0, $result['average']);
    }

    #[Test]
    public function it_determines_fail_for_course_below_10()
    {
        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'EXAM',
            'score' => 8,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->determinePassFail(
            $this->student->id,
            $this->enrollment->id
        );

        $this->assertEquals('course', $result['type']);
        $this->assertEquals('FAILED', $result['status']);
        $this->assertFalse($result['passed']);
    }

    #[Test]
    public function it_determines_semester_pass_fail_with_compensation()
    {
        ['course' => $course2, 'enrollment' => $enrollment2] = $this->createCourseWithEnrollment(
            'CS102',
            'Math',
            3,
            1
        );

        // Course 1: 12/20 (Pass)
        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'EXAM',
            'score' => 12,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        // Course 2: 9/20 (Compensable)
        Grade::create([
            'course_enrollment_id' => $enrollment2->id,
            'student_id' => $this->student->id,
            'course_id' => $course2->id,
            'type' => 'EXAM',
            'score' => 9,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->determinePassFail(
            $this->student->id,
            null,
            [$this->course->id, $course2->id]
        );

        // Semester average: (12 + 9) / 2 = 10.5 (Pass with compensation)
        $this->assertEquals('semester', $result['type']);
        $this->assertEquals('PASSED', $result['status']);
        $this->assertTrue($result['passed']);
        $this->assertTrue($result['compensation_applied']);
        $this->assertCount(1, $result['compensated_courses']);
    }

    #[Test]
    public function it_generates_complete_student_grade_report()
    {
        ['course' => $course2, 'enrollment' => $enrollment2] = $this->createCourseWithEnrollment(
            'CS102',
            'Math',
            3,
            2
        );

        // Course 1: 15/20
        Grade::create([
            'course_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'EXAM',
            'score' => 15,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        // Course 2: 12/20
        Grade::create([
            'course_enrollment_id' => $enrollment2->id,
            'student_id' => $this->student->id,
            'course_id' => $course2->id,
            'type' => 'EXAM',
            'score' => 12,
            'max_score' => 20,
            'weight' => 1.0,
            'entered_by' => $this->student->user_id,
            'status' => 'PUBLISHED',
        ]);

        $result = $this->service->getStudentGradeReport(
            $this->student->id,
            [$this->course->id, $course2->id]
        );

        $this->assertArrayHasKey('student_id', $result);
        $this->assertArrayHasKey('semester_average', $result);
        $this->assertArrayHasKey('gpa', $result);
        $this->assertArrayHasKey('total_credits', $result);
        $this->assertArrayHasKey('courses', $result);
        $this->assertArrayHasKey('compensation', $result);
        $this->assertArrayHasKey('overall_status', $result);
        $this->assertArrayHasKey('passed', $result);

        // Semester average: (15 * 2 + 12 * 2) / 4 = 13.5
        $this->assertEquals(13.5, $result['semester_average']);
        $this->assertEquals(3.15, $result['gpa']['gpa']);
        $this->assertEquals('PASSED', $result['overall_status']);
        $this->assertTrue($result['passed']);
    }

    #[Test]
    public function it_handles_incomplete_grades_in_course_average()
    {
        $result = $this->service->calculateCourseAverage($this->enrollment->id);

        $this->assertNull($result['average']);
        $this->assertEquals(0, $result['total_weight']);
        $this->assertFalse($result['is_complete']);
        $this->assertEmpty($result['grades_breakdown']);
    }
}
