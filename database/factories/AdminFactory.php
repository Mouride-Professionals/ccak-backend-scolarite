<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = [
            'Cheikh Ndiaye',
            'Aminata Diop',
            'Mamadou Ba',
            'Fatou Sow',
            'Ousmane Sarr',
            'Awa Gueye',
        ];

        return [
            'user_id' => User::factory(),
            'full_name' => $names[array_rand($names)],
        ];
    }
}
