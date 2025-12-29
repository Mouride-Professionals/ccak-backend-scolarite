<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $years = [
            '2020-2021',
            '2021-2022',
            '2022-2023',
            '2023-2024',
            '2024-2025',
            '2025-2026',
        ];

        foreach ($years as $year) {
            AcademicYear::firstOrCreate(['name' => $year]);
        }
    }
}
