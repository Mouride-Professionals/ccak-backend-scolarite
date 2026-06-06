<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GradeStatus;
use App\Enums\GradeType;
use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Grade> */
class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        $types = GradeType::cases();
        $type = $types[array_rand($types)];
        $statuses = GradeStatus::cases();
        $status = $statuses[array_rand($statuses)];
        $weights = [
            GradeType::CC->value => 0.30,
            GradeType::EXAM->value => 0.50,
            GradeType::TP->value => 0.15,
            GradeType::ORAL->value => 0.20,
        ];
        $score = $this->faker->randomFloat(2, 8, 20);

        return [
            'course_enrollment_id' => fn () => \App\Models\CourseEnrollment::factory(),
            'student_id' => fn () => \App\Models\Student::factory(),
            'course_id' => fn () => \App\Models\Course::factory(),
            'type' => $type->value,
            'score' => $score,
            'max_score' => 20.00,
            'weight' => $weights[$type->value] ?? 0.25,
            'entered_by' => fn () => \App\Models\User::factory(),
            'status' => $status->value,
            'entered_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'validated_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
        ];
    }
}
