<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DecisionType;
use App\Models\SemesterResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SemesterResult> */
class SemesterResultFactory extends Factory
{
    protected $model = SemesterResult::class;

    public function definition(): array
    {
        $decisions = DecisionType::cases();
        $decision = $decisions[array_rand($decisions)]->value;
        $creditsEnrolled = $this->faker->randomFloat(2, 24, 36);
        $semesterAverage = $this->faker->randomFloat(2, 8, 18);
        $creditsEarned = $semesterAverage >= 10
            ? $creditsEnrolled
            : $this->faker->randomFloat(2, $creditsEnrolled * 0.5, $creditsEnrolled * 0.9);

        return [
            'student_id' => fn () => \App\Models\Student::factory(),
            'academic_year_id' => fn () => \App\Models\AcademicYear::factory(),
            'semester' => $this->faker->numberBetween(1, 2),
            'total_credits_enrolled' => $creditsEnrolled,
            'total_credits_earned' => $creditsEarned,
            'semester_average' => $semesterAverage,
            'semester_gpa' => $this->faker->randomFloat(2, 1.0, 4.0),
            'decision' => $decision,
            'calculated_by' => fn () => \App\Models\User::factory(),
            'calculated_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
        ];
    }
}
