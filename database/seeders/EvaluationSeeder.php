<?php

namespace Database\Seeders;

use App\Models\Evaluation;
use App\Models\Schedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = Schedule::with('facultyMember')->get();
        if ($schedules->isEmpty()) {
            $this->call(ScheduleSeeder::class);
            $schedules = Schedule::with('facultyMember')->get();
        }

        $questions = [
            ['id' => 1, 'label' => 'Clarté du cours', 'type' => 'rating', 'scale' => 5],
            ['id' => 2, 'label' => 'Disponibilité de l\'enseignant', 'type' => 'rating', 'scale' => 5],
            ['id' => 3, 'label' => 'Qualité des supports', 'type' => 'rating', 'scale' => 5],
            ['id' => 4, 'label' => 'Commentaires', 'type' => 'text'],
        ];

        foreach ($schedules->take(8) as $schedule) {
            Evaluation::firstOrCreate(
                [
                    'course_id' => $schedule->course_id,
                    'faculty_member_id' => $schedule->faculty_member_id,
                ],
                [
                    'academic_year_id' => $schedule->academic_year_id,
                    'start_date' => Carbon::now()->subWeek()->toDateString(),
                    'end_date' => Carbon::now()->addWeeks(3)->toDateString(),
                    'question_template' => $questions,
                    'is_published' => true,
                    'response_deadline' => Carbon::now()->addWeeks(3),
                ]
            );
        }
    }
}
