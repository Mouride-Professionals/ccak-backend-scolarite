<?php

namespace Database\Seeders;

use App\Models\CourseLog;
use App\Models\Schedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CourseLogSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = Schedule::with(['facultyMember', 'course'])->get();
        if ($schedules->isEmpty()) {
            $this->call(ScheduleSeeder::class);
            $schedules = Schedule::with(['facultyMember', 'course'])->get();
        }

        foreach ($schedules as $schedule) {
            $start = $schedule->starts_on ? Carbon::parse($schedule->starts_on) : Carbon::now()->subWeeks(8);
            $dates = collect(range(0, 2))->map(function ($index) use ($start, $schedule) {
                $date = $start->copy()->addWeeks($index * 2);
                while ($date->dayOfWeekIso !== (int) $schedule->day_of_week) {
                    $date->addDay();
                }

                return $date;
            });

            foreach ($dates as $date) {
                CourseLog::firstOrCreate(
                    [
                        'schedule_id' => $schedule->id,
                        'session_date' => $date->toDateString(),
                    ],
                    [
                        'faculty_member_id' => $schedule->faculty_member_id,
                        'created_by_user_id' => $schedule->facultyMember?->user_id,
                        'topics' => ['Introduction', 'Application', 'Exercices'],
                        'chapters' => ['Chapitre 1', 'Chapitre 2'],
                        'objectives' => ['Comprendre les bases', 'Appliquer les notions'],
                        'notes' => 'Séance réalisée avec participation active.',
                        'signed_at' => $date->copy()->setTime(12, 0),
                    ]
                );
            }
        }
    }
}
