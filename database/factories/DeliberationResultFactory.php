<?php

namespace Database\Factories;

use App\Models\DeliberationResult;
use App\Models\DeliberationSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<DeliberationResult> */
class DeliberationResultFactory extends Factory
{
    protected $model = DeliberationResult::class;

    public function definition(): array
    {
        $decision = DeliberationResult::DECISIONS[array_rand(DeliberationResult::DECISIONS)];
        $remarks = [
            'Resultats satisfaisants',
            'Progression encourageante',
            'Doit renforcer les bases',
            'Travail reguliers souhaite',
            'Resultats insuffisants',
        ];

        // Logique: si ADMITTED, peut avoir mention; sinon pas de mention
        $isWithHonors = in_array($decision, [
            DeliberationResult::DECISION_ADMITTED,
            DeliberationResult::DECISION_ADMITTED_COMPENSATION,
        ]) && $this->faker->boolean(40); // 40% de chance

        return [
            'id' => Str::uuid()->toString(),
            'deliberation_session_id' => DeliberationSession::factory(),
            'student_id' => \App\Models\Student::factory(),
            'decision' => $decision,
            'jury_remarks' => $this->faker->optional(0.6)->passthrough($remarks[array_rand($remarks)]),
            'is_with_honors' => $isWithHonors,
            'honor_level' => $isWithHonors
                ? DeliberationResult::HONOR_LEVELS[array_rand(DeliberationResult::HONOR_LEVELS)]
                : null,
        ];
    }

    public function admitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'decision' => DeliberationResult::DECISION_ADMITTED,
            'is_with_honors' => $this->faker->boolean(60),
            'honor_level' => DeliberationResult::HONOR_LEVELS[array_rand(DeliberationResult::HONOR_LEVELS)],
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

    public function withHonors(?string $level = null): static
    {
        return $this->state(fn (array $attributes) => [
            'decision' => DeliberationResult::DECISION_ADMITTED,
            'is_with_honors' => true,
            'honor_level' => $level ?? DeliberationResult::HONOR_LEVELS[array_rand(DeliberationResult::HONOR_LEVELS)],
        ]);
    }
}
