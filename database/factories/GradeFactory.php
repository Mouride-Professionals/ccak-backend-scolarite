<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Grade> */
class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        return [
            'course_enrollment_id' => fn() => \App\Models\CourseEnrollment::factory(),
            'student_id' => fn() => \App\Models\Student::factory(),
            'course_id' => fn() => \App\Models\Course::factory(),
            'type' => $this->faker->sentence(),
            'score' => $this->faker->randomFloat(2, 0, 9999),
            'max_score' => $this->faker->randomFloat(2, 0, 9999),
            'weight' => $this->faker->randomFloat(2, 0, 9999),
            'entered_by' => fn() => \App\Models\User::factory(),
            'status' => $this->faker->sentence(),
            'entered_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'validated_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
        ];
    }
}
