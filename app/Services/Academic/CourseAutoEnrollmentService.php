<?php

namespace App\Services\Academic;

use App\Models\CourseEnrollment;
use App\Models\CourseUnit;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;

class CourseAutoEnrollmentService
{
    /**
     * Auto-enroll a student in all courses for every semester of their program.
     * Idempotent: skips courses already enrolled via firstOrCreate.
     *
     * @return array{enrolled: int, skipped: int}
     */
    public function enrollAllCourses(Enrollment $enrollment): array
    {
        $courseUnits = CourseUnit::with('courses')
            ->where('academic_program_id', $enrollment->academic_program_id)
            ->get();

        $enrolled = 0;
        $skipped = 0;

        DB::transaction(function () use ($enrollment, $courseUnits, &$enrolled, &$skipped) {
            foreach ($courseUnits as $unit) {
                foreach ($unit->courses as $course) {
                    $key = [
                        'enrollment_id' => $enrollment->id,
                        'course_id'     => $course->id,
                    ];

                    $attributes = [
                        'student_id'      => $enrollment->student_id,
                        'academic_year_id'=> $enrollment->academic_year_id,
                        'semester'        => $unit->semester_number,
                        'status'          => CourseEnrollment::STATUS_ENROLLED,
                        'enrollment_date' => $enrollment->enrollment_date ?? now(),
                    ];

                    [$record, $created] = [
                        CourseEnrollment::firstOrCreate($key, $attributes),
                        false,
                    ];

                    // firstOrCreate returns the model; check wasRecentlyCreated
                    $created = $record->wasRecentlyCreated;
                    $created ? $enrolled++ : $skipped++;
                }
            }
        });

        return ['enrolled' => $enrolled, 'skipped' => $skipped];
    }
}
