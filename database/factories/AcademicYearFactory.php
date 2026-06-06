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
        static $sequence = 0;
        $years = [
            '2019-2020',
            '2020-2021',
            '2021-2022',
            '2022-2023',
            '2023-2024',
            '2024-2025',
            '2025-2026',
        ];
        $year = $years[$sequence % count($years)];
        $sequence++;

        return [
            'name' => $year,
        ];
    }
}
