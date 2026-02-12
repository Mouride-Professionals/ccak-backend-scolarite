<?php

namespace App\Services\Academic;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Enums\GradeStatus;
use App\Models\Grade;

class EnrollmentValidationService
{
    /**
     * @return array{
     *   satisfied: bool,
     *   missing_required: array<int, string>,
     *   missing_optional: array<int, string>,
     *   missing_required_courses: array<int, string>,
     *   missing_optional_courses: array<int, string>
     * }
     */
    public function validatePrerequisites(string $studentId, Course $course): array
    {
        $rawPrerequisites = $course->prerequisites ?? [];
        $prerequisites = is_array($rawPrerequisites)
            ? $rawPrerequisites
            : (json_decode((string) $rawPrerequisites, true) ?? []);

        if ($prerequisites === []) {
            return [
                'satisfied' => true,
                'missing_required' => [],
                'missing_optional' => [],
                'missing_required_courses' => [],
                'missing_optional_courses' => [],
            ];
        }

        $passedByGrades = Grade::query()
            ->where('student_id', $studentId)
            ->whereIn('status', [GradeStatus::VALIDATED->value, GradeStatus::PUBLISHED->value])
            ->get(['course_id', 'score', 'max_score'])
            ->filter(function (Grade $grade): bool {
                if ((float) $grade->max_score <= 0) {
                    return false;
                }

                return (((float) $grade->score / (float) $grade->max_score) * 20) >= 10;
            })
            ->pluck('course_id')
            ->map(fn($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        $completedByEnrollments = CourseEnrollment::query()
            ->whereHas('enrollment', function ($query) use ($studentId): void {
                $query->where('student_id', $studentId);
            })
            ->where('status', CourseEnrollment::STATUS_COMPLETED)
            ->pluck('course_id')
            ->map(fn($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        $passedCourses = array_values(array_unique(array_merge($passedByGrades, $completedByEnrollments)));

        $missingRequired = [];
        $missingOptional = [];

        foreach ($prerequisites as $prerequisite) {
            $courseId = null;
            $isRequired = true;

            if (is_string($prerequisite)) {
                $courseId = $prerequisite;
            } elseif (is_array($prerequisite)) {
                $courseId = $prerequisite['prerequisite_course_id']
                    ?? $prerequisite['course_id']
                    ?? $prerequisite['id']
                    ?? null;
                $isRequired = (bool) ($prerequisite['is_required'] ?? true);
            }

            if (! is_string($courseId) || $courseId === '') {
                continue;
            }

            if (in_array($courseId, $passedCourses, true)) {
                continue;
            }

            if ($isRequired) {
                $missingRequired[] = $courseId;
            } else {
                $missingOptional[] = $courseId;
            }
        }

        return [
            'satisfied' => $missingRequired === [],
            'missing_required' => $missingRequired,
            'missing_optional' => $missingOptional,
            'missing_required_courses' => $this->courseNames($missingRequired),
            'missing_optional_courses' => $this->courseNames($missingOptional),
        ];
    }

    /**
     * @param array<int, string> $courseIds
     * @return array<int, string>
     */
    private function courseNames(array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }

        return Course::query()
            ->whereIn('id', $courseIds)
            ->pluck('name')
            ->values()
            ->all();
    }
}
