<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grade;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\Models\Enums\GradeType;
use App\Models\Enums\GradeStatus;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $enrollments = CourseEnrollment::with(['enrollment', 'course'])->get();
        $teachers = User::whereHas('roles', function ($query) {
            $query->where('name', 'TEACHER');
        })->get();

        if ($enrollments->isEmpty()) {
            $this->command->warn('No course enrollments found. Skipping grades.');
            return;
        }

        if ($teachers->isEmpty()) {
            $this->command->warn('No teachers found. Using admin user for grades.');
            $teachers = User::whereHas('roles', function ($query) {
                $query->where('name', 'ADMIN');
            })->get();
        }

        // Create 2-4 grades per enrollment (CC, EXAM, TP, etc.)
        $enrollments->each(function ($enrollment) use ($teachers) {
            $numberOfGrades = rand(2, 4);
            $gradeTypes = collect([GradeType::CC, GradeType::EXAM, GradeType::TP, GradeType::ORAL]);
            $selectedTypes = $gradeTypes->random(min($numberOfGrades, $gradeTypes->count()));

            foreach ($selectedTypes as $type) {
                $maxScore = 20.00; // Standard French grading scale
                $score = fake()->randomFloat(2, 8.00, 20.00); // Realistic student scores

                Grade::create([
                    'course_enrollment_id' => $enrollment->id,
                    'student_id' => $enrollment->student_id,
                    'course_id' => $enrollment->course_id,
                    'type' => $type,
                    'score' => $score,
                    'max_score' => $maxScore,
                    'weight' => $this->getWeightForType($type),
                    'entered_by' => $teachers->random()->id,
                    'status' => fake()->randomElement([GradeStatus::PUBLISHED, GradeStatus::VALIDATED]),
                    'entered_at' => now()->subDays(rand(1, 30)),
                    'validated_at' => now()->subDays(rand(0, 15)),
                ]);
            }
        });

        $this->command->info('Grades created successfully.');
    }

    private function getWeightForType(GradeType $type): float
    {
        return match ($type) {
            GradeType::CC => 0.30,      // Continuous assessment: 30%
            GradeType::EXAM => 0.50,    // Final exam: 50%
            GradeType::TP => 0.15,      // Practical work: 15%
            GradeType::ORAL => 0.20,    // Oral: 20%
        };
    }
}
