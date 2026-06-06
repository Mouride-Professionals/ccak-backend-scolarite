<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sequence = 1;
        $firstNames = ['mamadou', 'aminata', 'ousmane', 'fatou', 'cheikh', 'aissatou', 'ibrahima', 'awa'];
        $lastNames = ['ndiaye', 'diop', 'ba', 'sow', 'fall', 'gueye', 'cisse', 'seck'];
        $domains = ['ucak.sn', 'etu.ucak.sn', 'admin.ucak.sn'];
        $email = $firstNames[$sequence % count($firstNames)]
            .'.'
            .$lastNames[$sequence % count($lastNames)]
            .$sequence
            .'@'
            .$domains[$sequence % count($domains)];
        $sequence++;

        return [
            'id' => (string) Str::uuid(),
            'email' => $email,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
