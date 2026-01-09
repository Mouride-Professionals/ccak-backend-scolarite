<?php

namespace Database\Factories;

use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\DeliberationSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<DeliberationSession> */
class DeliberationSessionFactory extends Factory
{
    protected $model = DeliberationSession::class;

    public function definition(): array
    {
        $semester = $this->faker->numberBetween(1, 2);
        $sessionTypes = ['Normale', 'Rattrapage', 'Extraordinaire'];
        $levels = ['L1', 'L2', 'L3', 'M1', 'M2'];
        $sessionType = $sessionTypes[array_rand($sessionTypes)];
        $level = $levels[array_rand($levels)];

        return [
            'id' => Str::uuid()->toString(),
            'academic_program_id' => AcademicProgram::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'semester' => $semester,
            'session_name' => sprintf(
                'Jury %s S%d - Session %s',
                $level,
                $semester,
                $sessionType
            ),
            'session_date' => $this->faker->dateTimeBetween('-3 months', '+3 months'),
            'status' => $this->faker->randomElement(DeliberationSession::getStatuses()),
            'presided_by' => User::factory(),
            'jury_members' => null, // Sera rempli par le seeder
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliberationSession::STATUS_SCHEDULED,
            'session_date' => $this->faker->dateTimeBetween('+1 week', '+2 months'),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliberationSession::STATUS_COMPLETED,
            'session_date' => $this->faker->dateTimeBetween('-2 months', '-1 week'),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliberationSession::STATUS_IN_PROGRESS,
            'session_date' => now(),
        ]);
    }
}
