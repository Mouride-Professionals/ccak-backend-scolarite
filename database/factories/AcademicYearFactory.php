<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->numberBetween(2020, 2026);
        $endYear = $startYear + 1;

        return [
            'name' => "{$startYear}-{$endYear}",
        ];
    }
}
