<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sequence = 1;
        $year = date('Y');

        return [
            'user_id' => User::factory(),
            'student_number' => "UCAK{$year}" . str_pad((string)$sequence++, 3, '0', STR_PAD_LEFT),
            'full_name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['M', 'F']),
            'date_of_birth' => $this->faker->dateTimeBetween('-25 years', '-18 years'),
            'place_of_birth' => $this->faker->city(),
            'nationality' => $this->faker->country(),
            'phone' => $this->faker->phoneNumber(),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'photo_url' => $this->faker->imageUrl(),
            'status' => $this->faker->randomElement(Student::getStatuses()),
        ];
    }

    public function active(): static
    {
        return $this->state(fn() => [
            'status' => Student::STATUS_ACTIVE,
        ]);
    }

    public function graduated(): static
    {
        return $this->state(fn() => [
            'status' => Student::STATUS_GRADUATED,
        ]);
    }
}
