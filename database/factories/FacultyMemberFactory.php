<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FacultyMember>
 */
class FacultyMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'user_id' => $this->faker->uuid(),
            'staff_number' => fake()->text(),
            'full_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'department_id' => $this->faker->uuid(),
            'rank' => fake()->text(),
            'contract_type' => fake()->text(),
            'hire_date' => $this->faker->date(),
            'is_active' => $this->faker->boolean(),
            'created_at' => fake()->text(),
            'updated_at' => fake()->text(),
        ];
    }
}
