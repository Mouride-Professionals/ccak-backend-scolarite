<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Models\CourseEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CourseEnrollment> */
class CourseEnrollmentFactory extends Factory
{
    protected $model = CourseEnrollment::class;

    public function definition(): array
    {
        $statuses = \App\Models\CourseEnrollment::getStatuses();
        $status = $statuses[array_rand($statuses)];
        $enrollmentDate = $this->faker->date('Y-m-d');
        $dropDate = $status === \App\Models\CourseEnrollment::STATUS_DROPPED
            ? $this->faker->date('Y-m-d')
            : null;

        return [
            'student_id' => fn() => \App\Models\Student::factory(),
            'course_id' => fn() => \App\Models\Course::factory(),
            'enrollment_id' => fn() => \App\Models\Enrollment::factory(),
            'academic_year_id' => fn() => \App\Models\AcademicYear::factory(),
            'semester' => $this->faker->numberBetween(1, 12),
            'status' => $status,
            'enrollment_date' => $enrollmentDate,
            'drop_date' => $dropDate,
        ];
    }
}
