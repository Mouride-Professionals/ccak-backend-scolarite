<?php

namespace Database\Seeders;

use App\Models\Holiday;
use App\Models\AcademicYear;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class HolidaySeeder extends Seeder
{
    use UsesSenegalAcademicCalendar;

    public function run(): void
    {
        $years = AcademicYear::all();
        if ($years->isEmpty()) {
            $this->call(AcademicYearSeeder::class);
            $years = AcademicYear::all();
        }

        foreach ($years as $year) {
            [$startYear] = $this->parseAcademicYear($year->name);
            $rows = [
                ['date' => Carbon::create($startYear + 1, 4, 4), 'name' => 'Fête de l\'Indépendance', 'type' => 'NATIONAL', 'is_recurring' => true],
                ['date' => Carbon::create($startYear + 1, 5, 1), 'name' => 'Fête du Travail', 'type' => 'NATIONAL', 'is_recurring' => true],
                ['date' => Carbon::create($startYear, 12, 25), 'name' => 'Noël', 'type' => 'RELIGIOUS', 'is_recurring' => true],
                ['date' => Carbon::create($startYear + 1, 1, 1), 'name' => 'Nouvel An', 'type' => 'NATIONAL', 'is_recurring' => true],
                ['date' => Carbon::create($startYear + 1, 6, 15), 'name' => 'Pause académique', 'type' => 'ACADEMIC', 'is_recurring' => false],
            ];

            foreach ($rows as $row) {
                Holiday::firstOrCreate(
                    [
                        'academic_year_id' => $year->id,
                        'date' => $row['date']->toDateString(),
                    ],
                    [
                        'name' => $row['name'],
                        'type' => $row['type'],
                        'is_recurring' => $row['is_recurring'],
                    ]
                );
            }
        }
    }
}
