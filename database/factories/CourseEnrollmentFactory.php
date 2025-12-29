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
        return [
            'student_id' => fn() => \App\Models\Student::factory(),
            'course_id' => fn() => \App\Models\Course::factory(),
            'enrollment_id' => fn() => \App\Models\Enrollment::factory(),
            'academic_year_id' => fn() => \App\Models\AcademicYear::factory(),
            'semester' => $this->faker->numberBetween(1, 9999),
            'status' => $this->faker->sentence(),
            'enrollment_date' => $this->faker->date('Y-m-d'),
            'drop_date' => $this->faker->date('Y-m-d'),
        ];
    }
}
