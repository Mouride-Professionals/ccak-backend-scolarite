<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Models\SemesterResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SemesterResult> */
class SemesterResultFactory extends Factory
{
    protected $model = SemesterResult::class;

    public function definition(): array
    {
        return [
            'student_id' => fn() => \App\Models\Student::factory(),
            'academic_year_id' => fn() => \App\Models\AcademicYear::factory(),
            'semester' => $this->faker->numberBetween(1, 9999),
            'total_credits_enrolled' => $this->faker->randomFloat(2, 0, 9999),
            'total_credits_earned' => $this->faker->randomFloat(2, 0, 9999),
            'semester_average' => $this->faker->randomFloat(2, 0, 9999),
            'semester_gpa' => $this->faker->randomFloat(2, 0, 9999),
            'decision' => $this->faker->sentence(),
            'calculated_by' => fn() => \App\Models\User::factory(),
            'calculated_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
        ];
    }
}
