<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\CourseEnrollment;
use App\Models\SemesterResult;
use App\Models\Student;
use App\Models\User;
use App\Models\Enums\DecisionType;
use App\Repositories\SemesterResultRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SemesterResultCalculationService
{
    public function __construct(
        private readonly GradeCalculationService $gradeCalculationService,
        private readonly SemesterResultRepository $semesterResultRepository
    ) {}

    /**
     * Calculate semester results for all students in a specific semester and academic year
     *
     * @param string $academicYearId
     * @param int $semester
     * @param User $calculatedBy
     * @return array
     */
    /** @return array<string, mixed> */
    public function calculateSemesterResults(string $academicYearId, int $semester, User $calculatedBy): array
    {
        $academicYear = AcademicYear::findOrFail($academicYearId);

        Log::info("Starting semester results calculation", [
            'academic_year' => $academicYear->name,
            'semester' => $semester,
            'calculated_by' => $calculatedBy->id
        ]);

        // Get all students enrolled in courses for this semester
        $students = $this->getStudentsForSemester($academicYearId, $semester);

        if ($students->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No students found for this semester',
                'data' => [
                    'academic_year' => $academicYear->name,
                    'semester' => $semester,
                    'students_processed' => 0,
                    'results_created' => 0,
                ]
            ];
        }

        $resultsCreated = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($students as $student) {
                try {
                    $result = $this->calculateStudentSemesterResult(
                        $student,
                        $academicYearId,
                        $semester,
                        $calculatedBy
                    );

                    if ($result) {
                        $resultsCreated++;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'student_id' => $student->id,
                        'student_number' => $student->student_number,
                        'error' => $e->getMessage()
                    ];
                    Log::error("Error calculating semester result for student", [
                        'student_id' => $student->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            Log::info("Semester results calculation completed", [
                'students_processed' => $students->count(),
                'results_created' => $resultsCreated,
                'errors' => count($errors)
            ]);

            return [
                'success' => true,
                'message' => 'Semester results calculation completed successfully',
                'data' => [
                    'academic_year' => $academicYear->name,
                    'semester' => $semester,
                    'students_processed' => $students->count(),
                    'results_created' => $resultsCreated,
                    'errors' => $errors,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to calculate semester results", [
                'academic_year_id' => $academicYearId,
                'semester' => $semester,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to calculate semester results: ' . $e->getMessage(),
                'data' => [
                    'academic_year' => $academicYear->name,
                    'semester' => $semester,
                    'students_processed' => 0,
                    'results_created' => 0,
                    'errors' => $errors,
                ]
            ];
        }
    }

    /**
     * Calculate semester result for a specific student
     *
     * @param Student $student
     * @param string $academicYearId
     * @param int $semester
     * @param User $calculatedBy
     * @return SemesterResult|null
     */
    public function calculateStudentSemesterResult(
        Student $student,
        string $academicYearId,
        int $semester,
        User $calculatedBy
    ): ?SemesterResult {
        // Get courses for this student in the semester
        $studentId = (string) $student->id;
        $courseIds = $this->getStudentCoursesForSemester($studentId, $academicYearId, $semester);

        if (empty($courseIds)) {
            Log::warning("No courses found for student", [
                'student_id' => $student->id,
                'academic_year_id' => $academicYearId,
                'semester' => $semester
            ]);
            return null;
        }

        // Use the grade calculation service to get comprehensive report
        $gradeReport = $this->gradeCalculationService->getStudentGradeReport($studentId, $courseIds);

        // Calculate GPA for the semester
        $gpaData = $this->gradeCalculationService->calculateGPA($studentId, $courseIds);

        // Determine decision based on the results
        $decision = $this->determineDecision($gradeReport);

        // Calculate credits
        /** @var array<int, array<string, mixed>> $courses */
        $courses = $gradeReport['courses'];
        $totalCreditsEnrolled = collect($courses)->sum('coefficient');
        $totalCreditsEarned = $this->calculateCreditsEarned($gradeReport, $decision);

        // Check if result already exists and update or create
        $existingResult = SemesterResult::where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->first();

        $resultData = [
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId,
            'semester' => $semester,
            'total_credits_enrolled' => $totalCreditsEnrolled,
            'total_credits_earned' => $totalCreditsEarned,
            'semester_average' => $gradeReport['semester_average'] ?? 0,
            'semester_gpa' => $gpaData['gpa'] ?? 0,
            'decision' => $decision,
            'calculated_by' => $calculatedBy->id,
            'calculated_at' => now(),
        ];

        if ($existingResult) {
            return $this->semesterResultRepository->update($existingResult->id, $resultData);
        } else {
            return $this->semesterResultRepository->create($resultData);
        }
    }

    /**
     * Get all students enrolled in courses for a specific semester
     *
     * @param string $academicYearId
     * @param int $semester
     * @return Collection<int, Student>
     */
    private function getStudentsForSemester(string $academicYearId, int $semester): Collection
    {
        // For now, we'll get all students who have course enrollments
        // In a real implementation, you'd filter by academic year and semester
        return Student::whereHas('courseEnrollments')->get();
    }

    /**
     * Get course IDs for a student in a specific semester
     *
     * @param string $studentId
     * @param string $academicYearId
     * @param int $semester
     * @return array
     */
    /** @return array<int, string> */
    private function getStudentCoursesForSemester(string $studentId, string $academicYearId, int $semester): array
    {
        // For now, we'll get all courses for the student
        // In a real implementation, you'd filter by academic year and semester
        return CourseEnrollment::where('student_id', $studentId)
            ->pluck('course_id')
            ->toArray();
    }

    /**
     * Determine the decision based on grade report and compensation rules
     *
     * @param array $gradeReport
     * @return DecisionType
     */
    /** @param array<string, mixed> $gradeReport */
    private function determineDecision(array $gradeReport): DecisionType
    {
        $overallStatus = $gradeReport['overall_status'];
        $compensationData = $gradeReport['compensation'];
        $semesterAverage = $gradeReport['semester_average'] ?? 0;

        // If student passed outright
        if ($overallStatus === 'PASSED' && empty($compensationData['compensated_courses'])) {
            return DecisionType::VALIDATED;
        }

        // If student passed with compensation
        if ($overallStatus === 'PASSED' && !empty($compensationData['compensated_courses'])) {
            return DecisionType::COMPENSATION;
        }

        // If student failed but has decent average (might be eligible for resit)
        if ($overallStatus === 'FAILED' && $semesterAverage >= 8.0) {
            return DecisionType::RESIT_REQUIRED;
        }

        // Complete failure
        return DecisionType::FAILED;
    }

    /**
     * Calculate credits earned based on results and decision
     *
     * @param array $gradeReport
     * @param DecisionType $decision
     * @return float
     */
    /** @param array<string, mixed> $gradeReport */
    private function calculateCreditsEarned(array $gradeReport, DecisionType $decision): float
    {
        $totalCredits = 0;

        foreach ($gradeReport['courses'] as $courseData) {
            $coefficient = $courseData['coefficient'] ?? 0;

            // Award credits for passed courses
            if ($courseData['average'] >= 10) {
                $totalCredits += $coefficient;
            }
        }

        // Add credits for compensated courses if decision allows
        if (in_array($decision, [DecisionType::VALIDATED, DecisionType::COMPENSATION])) {
            foreach ($gradeReport['compensation']['compensated_courses'] as $compensatedCourse) {
                // Find the coefficient for the compensated course
                foreach ($gradeReport['courses'] as $courseData) {
                    if ($courseData['course_id'] === $compensatedCourse['course_id']) {
                        $totalCredits += $courseData['coefficient'] ?? 0;
                        break;
                    }
                }
            }
        }

        return $totalCredits;
    }

    /**
     * Get semester results statistics
     *
     * @param string $academicYearId
     * @param int $semester
     * @return array
     */
    /** @return array<string, mixed> */
    public function getSemesterStatistics(string $academicYearId, int $semester): array
    {
        $results = SemesterResult::where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->get();

        if ($results->isEmpty()) {
            return [
                'total_students' => 0,
                'decisions' => [],
                'average_gpa' => 0,
                'average_semester_average' => 0,
            ];
        }

        $decisionCounts = $results->countBy('decision');

        return [
            'total_students' => $results->count(),
            'decisions' => [
                'VALIDATED' => $decisionCounts[DecisionType::VALIDATED->value] ?? 0,
                'COMPENSATION' => $decisionCounts[DecisionType::COMPENSATION->value] ?? 0,
                'RESIT_REQUIRED' => $decisionCounts[DecisionType::RESIT_REQUIRED->value] ?? 0,
                'FAILED' => $decisionCounts[DecisionType::FAILED->value] ?? 0,
            ],
            'success_rate' => round((($decisionCounts[DecisionType::VALIDATED->value] ?? 0) +
                                   ($decisionCounts[DecisionType::COMPENSATION->value] ?? 0)) / $results->count() * 100, 2),
            'average_gpa' => round($results->avg('semester_gpa'), 3),
            'average_semester_average' => round($results->avg('semester_average'), 2),
        ];
    }
}
