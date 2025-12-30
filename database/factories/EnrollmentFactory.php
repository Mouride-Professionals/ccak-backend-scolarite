<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Enrollment> */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'student_id' => fn() => \App\Models\Student::factory(),
            'academic_program_id' => fn() => \App\Models\AcademicProgram::factory(),
            'academic_year_id' => fn() => \App\Models\AcademicYear::factory(),
            'current_semester' => $this->faker->numberBetween(1, 12),
            'status' => $this->faker->randomElement(['PENDING', 'REGISTERED', 'ACTIVE', 'COMPLETED', 'WITHDRAWN']),
            'enrollment_date' => $this->faker->date('Y-m-d'),
            'registration_fee_paid' => $this->faker->randomFloat(2, 0, 9999),
            'is_scholarship' => $this->faker->boolean(),
        ];
    }
}
