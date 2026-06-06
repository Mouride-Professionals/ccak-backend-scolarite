<?php

namespace App\Services\Authorization;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Enrollment;
use App\Models\Student;

class EnrollmentService
{
    /**
     * Validate all enrollment rules
     *
     * @return array<string, mixed>
     */
    public function validateEnrollment(string $studentId, string $courseId, string $academicYearId, int $semester): array
    {
        $validations = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'details' => [],
        ];

        // 1. Check academic standing
        $standingCheck = $this->checkAcademicStanding($studentId);
        $validations['details']['academic_standing'] = $standingCheck;
        if (! $standingCheck['eligible']) {
            $validations['valid'] = false;
            $validations['errors'][] = $standingCheck['reason'];
        }

        // 2. Check fee payment
        $feeCheck = $this->checkFeePayment($studentId, $academicYearId);
        $validations['details']['fee_payment'] = $feeCheck;
        if (! $feeCheck['paid']) {
            $validations['valid'] = false;
            $validations['errors'][] = $feeCheck['reason'];
        }

        // 3. Check prerequisites
        $prerequisiteCheck = $this->checkPrerequisites($studentId, $courseId);
        $validations['details']['prerequisites'] = $prerequisiteCheck;
        if (! $prerequisiteCheck['satisfied']) {
            $validations['valid'] = false;
            $validations['errors'][] = 'Prérequis non satisfaits: '.implode(', ', $prerequisiteCheck['missing_courses']);
        }

        // 4. Check capacity
        $capacityCheck = $this->checkCapacity($courseId, $academicYearId, $semester);
        $validations['details']['capacity'] = $capacityCheck;
        if (! $capacityCheck['available']) {
            $validations['valid'] = false;
            $validations['errors'][] = $capacityCheck['reason'];
        }

        // 5. Check time conflicts
        $conflictCheck = $this->checkTimeConflicts($studentId, $courseId, $academicYearId, $semester);
        $validations['details']['time_conflicts'] = $conflictCheck;
        if ($conflictCheck['has_conflicts']) {
            $validations['valid'] = false;
            $validations['errors'][] = 'Conflit d\'horaire avec: '.implode(', ', $conflictCheck['conflicting_courses']);
        }

        return $validations;
    }

    /**
     * Check if student is in good academic standing
     *
     * @return array<string, mixed>
     */
    private function checkAcademicStanding(string $studentId): array
    {
        $student = Student::find($studentId);

        if (! $student) {
            return [
                'eligible' => false,
                'reason' => 'Étudiant non trouvé.',
            ];
        }

        if ($student->status !== Student::STATUS_ACTIVE) {
            return [
                'eligible' => false,
                'reason' => 'Compte étudiant inactif.',
            ];
        }

        // Check if student has active enrollment
        $hasActiveEnrollment = Enrollment::where('student_id', $studentId)
            ->whereIn('status', ['ACTIVE', 'REGISTERED'])
            ->exists();

        if (! $hasActiveEnrollment) {
            return [
                'eligible' => false,
                'reason' => 'Aucune inscription active trouvée.',
            ];
        }

        return [
            'eligible' => true,
            'reason' => 'En règle académique.',
        ];
    }

    /**
     * Check if registration fees are paid
     *
     * @return array<string, mixed>
     */
    private function checkFeePayment(string $studentId, string $academicYearId): array
    {
        $enrollment = Enrollment::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('status', ['ACTIVE', 'REGISTERED'])
            ->first();

        if (! $enrollment) {
            return [
                'paid' => false,
                'reason' => 'Aucune inscription pour cette année académique.',
                'amount_due' => 0,
            ];
        }

        // If scholarship, no fees required
        if ($enrollment->is_scholarship_holder) {
            return [
                'paid' => true,
                'reason' => 'Étudiant boursier - pas de frais requis.',
                'amount_due' => 0,
            ];
        }

        // Check if fees are paid (you can define minimum amount)
        $minimumFees = 1000; // Example: minimum required fees
        $isPaid = $enrollment->registration_fee_paid >= $minimumFees;

        return [
            'paid' => $isPaid,
            'reason' => $isPaid ? 'Frais d\'inscription payés.' : 'Frais d\'inscription incomplets.',
            'amount_paid' => $enrollment->registration_fee_paid,
            'amount_due' => $isPaid ? 0 : ($minimumFees - $enrollment->registration_fee_paid),
        ];
    }

    /**
     * Check if prerequisites are satisfied
     *
     * @return array<string, mixed>
     */
    private function checkPrerequisites(string $studentId, string $courseId): array
    {
        $course = Course::find($courseId);

        if (! $course) {
            return [
                'satisfied' => false,
                'reason' => 'Cours non trouvé.',
                'missing_courses' => [],
            ];
        }

        /** @var array<int, string> $prerequisites */
        $prerequisites = $course->prerequisites ?? [];

        if (empty($prerequisites)) {
            return [
                'satisfied' => true,
                'reason' => 'Aucun prérequis requis.',
                'missing_courses' => [],
            ];
        }

        // Get completed courses for this student
        $completedCourses = CourseEnrollment::whereHas('enrollment', function ($query) use ($studentId) {
            $query->where('student_id', $studentId);
        })
            ->where('status', 'COMPLETED')
            ->pluck('course_id')
            ->toArray();

        $missingPrerequisites = array_diff($prerequisites, $completedCourses);

        if (! empty($missingPrerequisites)) {
            $missingCourseNames = Course::whereIn('id', $missingPrerequisites)
                ->pluck('name')
                ->toArray();

            return [
                'satisfied' => false,
                'reason' => 'Prérequis manquants.',
                'missing_courses' => $missingCourseNames,
            ];
        }

        return [
            'satisfied' => true,
            'reason' => 'Tous les prérequis satisfaits.',
            'missing_courses' => [],
        ];
    }

    /**
     * Check course capacity
     *
     * @return array<string, mixed>
     */
    private function checkCapacity(string $courseId, string $academicYearId, int $semester): array
    {
        $course = Course::find($courseId);

        if (! $course) {
            return [
                'available' => false,
                'reason' => 'Cours non trouvé.',
            ];
        }

        // Count current enrollments
        $currentEnrollments = CourseEnrollment::where('course_id', $courseId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('status', 'ENROLLED')
            ->count();

        // Default max capacity if not set
        $maxCapacity = 50; // You can add this field to your courses table if needed

        $available = $currentEnrollments < $maxCapacity;

        return [
            'available' => $available,
            'reason' => $available ? 'Places disponibles.' : 'Cours complet.',
            'current_enrollment' => $currentEnrollments,
            'max_capacity' => $maxCapacity,
            'remaining_seats' => max(0, $maxCapacity - $currentEnrollments),
        ];
    }

    /**
     * Check for time conflicts with other enrolled courses
     *
     * @return array<string, mixed>
     */
    private function checkTimeConflicts(string $studentId, string $courseId, string $academicYearId, int $semester): array
    {
        // Get student's current course enrollments for this semester
        $enrolledCourses = CourseEnrollment::whereHas('enrollment', function ($query) use ($studentId) {
            $query->where('student_id', $studentId);
        })
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('status', 'ENROLLED')
            ->with('course')
            ->get();

        // If you have a schedule/timetable table, check actual time conflicts here
        // For now, we'll just check if the course is already enrolled
        $alreadyEnrolled = $enrolledCourses->contains('course_id', $courseId);

        if ($alreadyEnrolled) {
            return [
                'has_conflicts' => true,
                'reason' => 'Déjà inscrit à ce cours.',
                'conflicting_courses' => [],
            ];
        }

        // You can implement actual time conflict checking here
        // by comparing course schedules if you have that data

        return [
            'has_conflicts' => false,
            'reason' => 'Aucun conflit d\'horaire.',
            'conflicting_courses' => [],
        ];
    }

    /**
     * Validate and enroll (convenience method)
     *
     * @return array<string, mixed>
     */
    public function validateAndEnroll(string $studentId, string $courseId, string $academicYearId, int $semester): array
    {
        $validation = $this->validateEnrollment($studentId, $courseId, $academicYearId, $semester);

        if (! $validation['valid']) {
            return [
                'success' => false,
                'message' => 'Validation échouée.',
                'validation' => $validation,
            ];
        }

        // Proceed with enrollment if validation passes
        return [
            'success' => true,
            'message' => 'Validation réussie. Prêt pour l\'inscription.',
            'validation' => $validation,
        ];
    }
}
