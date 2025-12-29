<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    use UsesSenegalAcademicCalendar;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $current = $this->currentAcademicYearName();
        [$startYear] = $this->parseAcademicYear($current);
        $years = [];

        for ($year = $startYear - 2; $year <= $startYear + 3; $year++) {
            $years[] = sprintf('%d-%d', $year, $year + 1);
        }

        foreach ($years as $year) {
            AcademicYear::firstOrCreate(['name' => $year]);
        }
    }
}
