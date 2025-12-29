<?php

namespace Database\Factories;

use App\Models\DeliberationResult;
use App\Models\DeliberationSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeliberationResultFactory extends Factory
{
    protected $model = DeliberationResult::class;

    public function definition(): array
    {
        $decision = $this->faker->randomElement(DeliberationResult::DECISIONS);

        // Logique: si ADMITTED, peut avoir mention; sinon pas de mention
        $isWithHonors = in_array($decision, [
            DeliberationResult::DECISION_ADMITTED,
            DeliberationResult::DECISION_ADMITTED_COMPENSATION
        ]) && $this->faker->boolean(40); // 40% de chance

        return [
            'id' => Str::uuid()->toString(),
            'deliberation_session_id' => DeliberationSession::factory(),
            'student_id' => $this->faker->uuid,
            'decision' => $decision,
            'jury_remarks' => $this->faker->optional(0.6)->paragraph(),
            'is_with_honors' => $isWithHonors,
            'honor_level' => $isWithHonors
                ? $this->faker->randomElement(DeliberationResult::HONOR_LEVELS)
                : null,
        ];
    }

    public function admitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'decision' => DeliberationResult::DECISION_ADMITTED,
            'is_with_honors' => $this->faker->boolean(60),
            'honor_level' => $this->faker->randomElement(DeliberationResult::HONOR_LEVELS),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'decision' => DeliberationResult::DECISION_FAILED,
            'is_with_honors' => false,
            'honor_level' => null,
            'jury_remarks' => 'Résultats insuffisants',
        ]);
    }

    public function withHonors(string $level = null): static
    {
        return $this->state(fn (array $attributes) => [
            'decision' => DeliberationResult::DECISION_ADMITTED,
            'is_with_honors' => true,
            'honor_level' => $level ?? $this->faker->randomElement(DeliberationResult::HONOR_LEVELS),
        ]);
    }
}
