<?php

namespace Database\Seeders;

use App\Models\AcademicCalendar;
use App\Models\AcademicYear;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Database\Seeder;

class AcademicCalendarSeeder extends Seeder
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
            $start = $this->academicYearStart($year->name)->toDateString();
            $end = $this->academicYearEnd($year->name)->toDateString();

            AcademicCalendar::updateOrCreate(
                ['academic_year_id' => $year->id],
                [
                    'start_date' => $start,
                    'end_date' => $end,
                    'working_days' => [1, 2, 3, 4, 5, 6],
                    'weekend_days' => [7],
                    'hour_slots' => [
                        ['start' => '08:00', 'end' => '10:00'],
                        ['start' => '10:15', 'end' => '12:15'],
                        ['start' => '14:00', 'end' => '16:00'],
                        ['start' => '16:15', 'end' => '18:15'],
                    ],
                    'break_slots' => [
                        ['start' => '12:15', 'end' => '14:00', 'label' => 'Pause déjeuner'],
                    ],
                ]
            );
        }
    }
}
