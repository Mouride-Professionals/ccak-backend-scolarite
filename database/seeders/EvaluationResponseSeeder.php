<?php

namespace Database\Seeders;

use App\Models\CourseEnrollment;
use App\Models\Evaluation;
use App\Models\EvaluationResponse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EvaluationResponseSeeder extends Seeder
{
    public function run(): void
    {
        $evaluations = Evaluation::all();
        if ($evaluations->isEmpty()) {
            $this->call(EvaluationSeeder::class);
            $evaluations = Evaluation::all();
        }

        foreach ($evaluations as $evaluation) {
            $students = CourseEnrollment::query()
                ->where('course_id', $evaluation->course_id)
                ->when($evaluation->academic_year_id, function ($query) use ($evaluation) {
                    $query->where('academic_year_id', $evaluation->academic_year_id);
                })
                ->where('status', CourseEnrollment::STATUS_ENROLLED)
                ->pluck('student_id')
                ->take(10);

            foreach ($students as $studentId) {
                EvaluationResponse::firstOrCreate(
                    [
                        'evaluation_id' => $evaluation->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'responses' => [
                            ['question_id' => 1, 'value' => random_int(3, 5)],
                            ['question_id' => 2, 'value' => random_int(2, 5)],
                            ['question_id' => 3, 'value' => random_int(3, 5)],
                        ],
                        'rating_scores' => [random_int(3, 5), random_int(2, 5), random_int(3, 5)],
                        'comments' => 'Cours enrichissant et bien structuré.',
                        'is_anonymous' => (bool) random_int(0, 1),
                        'submitted_at' => Carbon::now()->subDays(random_int(1, 10)),
                    ]
                );
            }
        }
    }
}
