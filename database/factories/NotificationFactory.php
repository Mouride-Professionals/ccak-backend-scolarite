<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(Notification::getTypes()),
            'channel' => $this->faker->randomElement(Notification::getChannels()),
            'title' => $this->faker->sentence(4),
            'message' => $this->faker->sentence(12),
            'metadata' => [
                'source' => 'factory',
            ],
            'is_read' => false,
            'read_at' => null,
        ];
    }
}
