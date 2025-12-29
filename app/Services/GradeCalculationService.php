<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Grade;
use App\Models\Course;

/**
 * Service for handling grade calculations
 * Includes course averages, semester averages, GPA, compensation rules, and pass/fail logic
 */
class GradeCalculationService
{

    /**
     * Minimum passing grade (out of 20)
     */
    private const PASSING_GRADE = 10.0;

    /**
     * Minimum grade required for compensation
     */
    private const COMPENSATION_MIN = 8.0;

    /**
     * Minimum semester average required for compensation to apply
     */
    private const COMPENSATION_SEMESTER_MIN = 10.0;

    /**
     * Calculate the average for a single course based on all grades (CC, EXAM, TP, ORAL)
     *
     * @param string $courseEnrollmentId The course enrollment ID
     * @return array<string, mixed> Contains 'average', 'total_weight', 'grades_breakdown', 'is_complete'
     */
    public function calculateCourseAverage(string $courseEnrollmentId): array
    {
        $grades = Grade::where('course_enrollment_id', $courseEnrollmentId)
            ->where('status', 'PUBLISHED')
            ->get();

        if ($grades->isEmpty()) {
            return [
                'average' => null,
                'total_weight' => 0,
                'grades_breakdown' => [],
                'is_complete' => false,
            ];
        }

        $weightedSum = 0;
        $totalWeight = 0;
        $gradesBreakdown = [];

        foreach ($grades as $grade) {
            // Normalize score to /20 scale
            $normalizedScore = ($grade->score / $grade->max_score) * 20;
            $weightedScore = $normalizedScore * $grade->weight;

            $weightedSum += $weightedScore;
            $totalWeight += $grade->weight;

            $gradesBreakdown[] = [
                'type' => $grade->type,
                'score' => $grade->score,
                'max_score' => $grade->max_score,
                'normalized_score' => round($normalizedScore, 2),
                'weight' => $grade->weight,
                'weighted_score' => round($weightedScore, 2),
            ];
        }

        // Check if weights sum to 1.0 (100%)
        $isComplete = abs($totalWeight - 1.0) < 0.01;

        $average = $totalWeight > 0 ? $weightedSum / $totalWeight : null;

        return [
            'average' => $average !== null ? round($average, 2) : null,
            'total_weight' => round($totalWeight, 2),
            'grades_breakdown' => $gradesBreakdown,
            'is_complete' => $isComplete,
        ];
    }

    /**
     * Calculate semester average for a student
     *
     * @param string $studentId The student ID
     * @param array<int, string> $courseIds Array of course IDs to include in calculation
     * @return array<string, mixed> Contains 'semester_average', 'courses', 'total_credits', 'weighted_sum'
     */
    public function calculateSemesterAverage(string $studentId, array $courseIds): array
    {
        $courses = Course::whereIn('id', $courseIds)->get();
        $coursesData = [];
        $totalWeightedSum = 0;
        $totalCredits = 0;

        foreach ($courses as $course) {
            // Get the course enrollment for this student and course
            $enrollment = \App\Models\CourseEnrollment::where('student_id', $studentId)
                ->where('course_id', $course->id)
                ->first();

            if (!$enrollment) {
                continue;
            }

            $courseAvgData = $this->calculateCourseAverage((string) $enrollment->id);

            if ($courseAvgData['average'] !== null && $courseAvgData['is_complete']) {
                $coefficient = $course->coefficient ?? 1;
                $weightedScore = $courseAvgData['average'] * $coefficient;

                $totalWeightedSum += $weightedScore;
                $totalCredits += $coefficient;

                $coursesData[] = [
                    'course_id' => $course->id,
                    'course_code' => $course->code,
                    'course_name' => $course->name,
                    'average' => $courseAvgData['average'],
                    'coefficient' => $coefficient,
                    'weighted_score' => round($weightedScore, 2),
                    'is_complete' => $courseAvgData['is_complete'],
                ];
            }
        }

        $semesterAverage = $totalCredits > 0 ? $totalWeightedSum / $totalCredits : null;

        return [
            'semester_average' => $semesterAverage !== null ? round($semesterAverage, 2) : null,
            'courses' => $coursesData,
            'total_credits' => $totalCredits,
            'weighted_sum' => round($totalWeightedSum, 2),
        ];
    }

    /**
     * Apply compensation rules to determine if a student passes
     *
     * Compensation rules:
     * - A student can pass a course with grade >= 8 and < 10 if semester average >= 10
     * - Courses with grade < 8 cannot be compensated
     *
     * @param string $studentId The student ID
     * @param array<int, string> $courseIds Array of course IDs for the semester
     * @return array<string, mixed> Contains compensation details and which courses are compensated
     */
    public function applyCompensationRules(string $studentId, array $courseIds): array
    {
        $semesterData = $this->calculateSemesterAverage($studentId, $courseIds);
        $semesterAverage = $semesterData['semester_average'];

        if ($semesterAverage === null) {
            return [
                'can_compensate' => false,
                'semester_average' => null,
                'compensated_courses' => [],
                'failed_courses' => [],
                'reason' => 'Semester average not available',
            ];
        }

        $compensatedCourses = [];
        $failedCourses = [];
        $canCompensate = $semesterAverage >= self::COMPENSATION_SEMESTER_MIN;

        foreach ($semesterData['courses'] as $courseData) {
            $average = $courseData['average'];

            if ($average < self::PASSING_GRADE) {
                if ($average >= self::COMPENSATION_MIN && $canCompensate) {
                    // Can be compensated
                    $compensatedCourses[] = [
                        'course_id' => $courseData['course_id'],
                        'course_code' => $courseData['course_code'],
                        'course_name' => $courseData['course_name'],
                        'average' => $average,
                        'status' => 'COMPENSATED',
                    ];
                } else {
                    // Cannot be compensated
                    $failedCourses[] = [
                        'course_id' => $courseData['course_id'],
                        'course_code' => $courseData['course_code'],
                        'course_name' => $courseData['course_name'],
                        'average' => $average,
                        'status' => 'FAILED',
                        'reason' => $average < self::COMPENSATION_MIN
                            ? 'Grade too low for compensation (< 8)'
                            : 'Semester average too low for compensation',
                    ];
                }
            }
        }

        return [
            'can_compensate' => $canCompensate,
            'semester_average' => $semesterAverage,
            'compensated_courses' => $compensatedCourses,
            'failed_courses' => $failedCourses,
        ];
    }

    /**
     * Determine if a student passes or fails a course or semester
     *
     * @param string $studentId The student ID
     * @param string|null $courseEnrollmentId Optional course enrollment ID for single course check
     * @param array<int, string>|null $courseIds Optional array of course IDs for semester check
     * @return array<string, mixed> Contains pass/fail status and details
     */
    public function determinePassFail(
        string $studentId,
        ?string $courseEnrollmentId = null,
        ?array $courseIds = null
    ): array {
        // Single course evaluation
        if ($courseEnrollmentId !== null) {
            $courseData = $this->calculateCourseAverage($courseEnrollmentId);

            if ($courseData['average'] === null) {
                return [
                    'type' => 'course',
                    'status' => 'INCOMPLETE',
                    'average' => null,
                    'passed' => false,
                    'reason' => 'Grades not available or incomplete',
                ];
            }

            $passed = $courseData['average'] >= self::PASSING_GRADE;

            return [
                'type' => 'course',
                'status' => $passed ? 'PASSED' : 'FAILED',
                'average' => $courseData['average'],
                'passed' => $passed,
                'details' => $courseData,
            ];
        }

        // Semester evaluation with compensation
        if ($courseIds !== null) {
            $compensationData = $this->applyCompensationRules($studentId, $courseIds);
            $semesterPassed = empty($compensationData['failed_courses'])
                && $compensationData['semester_average'] >= self::PASSING_GRADE;

            return [
                'type' => 'semester',
                'status' => $semesterPassed ? 'PASSED' : 'FAILED',
                'semester_average' => $compensationData['semester_average'],
                'passed' => $semesterPassed,
                'compensation_applied' => $compensationData['can_compensate'],
                'compensated_courses' => $compensationData['compensated_courses'],
                'failed_courses' => $compensationData['failed_courses'],
            ];
        }

        return [
            'type' => 'unknown',
            'status' => 'ERROR',
            'passed' => false,
            'reason' => 'Either courseEnrollmentId or courseIds must be provided',
        ];
    }

    /**
     * Convert a grade on 20-point scale to 4-point GPA scale
     *
     * @param float $grade Grade on 20-point scale
     * @return float GPA on 4-point scale
     */
    public function convertToGpaScale(float $grade): float
    {
        if ($grade >= 18) return 4.0;  // A+ (18-20)
        if ($grade >= 16) return 3.7;  // A  (16-17.99)
        if ($grade >= 14) return 3.3;  // B+ (14-15.99)
        if ($grade >= 12) return 3.0;  // B  (12-13.99)
        if ($grade >= 10) return 2.7;  // C+ (10-11.99)
        if ($grade >= 8) return 2.0;   // C  (8-9.99)
        if ($grade >= 6) return 1.0;   // D  (6-7.99)
        return 0.0;                    // F  (0-5.99)
    }

    /**
     * Calculate GPA for a student for a specific semester
     *
     * @param string $studentId The student ID
     * @param array<int, string> $courseIds Array of course IDs for the semester
     * @return array<string, mixed> Contains GPA and detailed calculation
     */
    public function calculateGPA(string $studentId, array $courseIds): array
    {
        $semesterData = $this->calculateSemesterAverage($studentId, $courseIds);

        if (empty($semesterData['courses'])) {
            return [
                'gpa' => null,
                'total_credits' => 0,
                'courses_gpa' => [],
                'grade_points' => 0,
            ];
        }

        $totalGradePoints = 0;
        $totalCredits = 0;
        $coursesGpa = [];

        foreach ($semesterData['courses'] as $courseData) {
            $average = $courseData['average'];
            $coefficient = $courseData['coefficient'];

            if ($average !== null) {
                $gpa = $this->convertToGpaScale($average);
                $gradePoints = $gpa * $coefficient;

                $totalGradePoints += $gradePoints;
                $totalCredits += $coefficient;

                $coursesGpa[] = [
                    'course_id' => $courseData['course_id'],
                    'course_code' => $courseData['course_code'],
                    'course_name' => $courseData['course_name'],
                    'average' => $average,
                    'gpa' => round($gpa, 2),
                    'credits' => $coefficient,
                    'grade_points' => round($gradePoints, 2),
                    'letter_grade' => $this->getLetterGrade($average),
                ];
            }
        }

        $gpa = $totalCredits > 0 ? $totalGradePoints / $totalCredits : null;

        return [
            'gpa' => $gpa !== null ? round($gpa, 3) : null,
            'total_credits' => $totalCredits,
            'courses_gpa' => $coursesGpa,
            'grade_points' => round($totalGradePoints, 2),
        ];
    }

    /**
     * Calculate cumulative GPA across multiple semesters
     *
     * @param string $studentId The student ID
     * @param array<int, array<int, string>> $semesterCourses Array of arrays, each containing course IDs for a semester
     * @return array<string, mixed> Contains cumulative GPA and semester breakdown
     */
    public function calculateCumulativeGPA(string $studentId, array $semesterCourses): array
    {
        $semesters = [];
        $totalGradePoints = 0;
        $totalCredits = 0;

        foreach ($semesterCourses as $index => $courseIds) {
            $semesterGpa = $this->calculateGPA($studentId, $courseIds);

            if ($semesterGpa['gpa'] !== null) {
                $semesters[] = [
                    'semester' => $index + 1,
                    'gpa' => $semesterGpa['gpa'],
                    'credits' => $semesterGpa['total_credits'],
                    'grade_points' => $semesterGpa['grade_points'],
                    'courses' => $semesterGpa['courses_gpa'],
                ];

                $totalGradePoints += $semesterGpa['grade_points'];
                $totalCredits += $semesterGpa['total_credits'];
            }
        }

        $cumulativeGpa = $totalCredits > 0 ? $totalGradePoints / $totalCredits : null;

        return [
            'cumulative_gpa' => $cumulativeGpa !== null ? round($cumulativeGpa, 3) : null,
            'total_credits' => $totalCredits,
            'total_grade_points' => round($totalGradePoints, 2),
            'semesters' => $semesters,
        ];
    }

    /**
     * Get letter grade for a numerical grade
     *
     * @param float $grade Grade on 20-point scale
     * @return string Letter grade
     */
    public function getLetterGrade(float $grade): string
    {
        if ($grade >= 18) return 'A+';
        if ($grade >= 16) return 'A';
        if ($grade >= 14) return 'B+';
        if ($grade >= 12) return 'B';
        if ($grade >= 10) return 'C+';
        if ($grade >= 8) return 'C';
        if ($grade >= 6) return 'D';
        return 'F';
    }

    /**
     * Get comprehensive grade report for a student
     *
     * @param string $studentId The student ID
     * @param array<int, string> $courseIds Array of course IDs for the semester
     * @return array<string, mixed> Complete grade report with all calculations
     */
    public function getStudentGradeReport(string $studentId, array $courseIds): array
    {
        $semesterData = $this->calculateSemesterAverage($studentId, $courseIds);
        $compensationData = $this->applyCompensationRules($studentId, $courseIds);
        $passFailData = $this->determinePassFail($studentId, null, $courseIds);
        $gpaData = $this->calculateGPA($studentId, $courseIds);

        return [
            'student_id' => $studentId,
            'semester_average' => $semesterData['semester_average'],
            'total_credits' => $semesterData['total_credits'],
            'courses' => $semesterData['courses'],
            'compensation' => $compensationData,
            'overall_status' => $passFailData['status'],
            'passed' => $passFailData['passed'],
            'gpa' => $gpaData,
        ];
    }
}
