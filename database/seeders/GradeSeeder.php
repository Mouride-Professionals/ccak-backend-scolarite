<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GradeStatus;
use App\Enums\GradeType;
use App\Models\CourseEnrollment;
use App\Models\Grade;
use App\Models\User;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class GradeSeeder extends Seeder
{
    use UsesSenegalAcademicCalendar;

    public function run(): void
    {
        $enrollments = CourseEnrollment::with(['enrollment.academicYear', 'enrollment.student', 'academicYear', 'course'])->get();
        $teachers = User::role('FACULTY')->get();

        if ($enrollments->isEmpty()) {
            $this->command->warn('No course enrollments found. Skipping grades.');

            return;
        }

        if ($teachers->isEmpty()) {
            $teachers = User::role('ADMIN')->get();
        }

        if ($teachers->isEmpty()) {
            $this->command->warn('No faculty/admin users found. Creating a fallback faculty user.');
            $fallback = User::factory()->create([
                'email' => 'faculty.seed@ucak.sn',
                'email_verified_at' => Carbon::now('Africa/Dakar'),
            ]);
            $fallback->assignRole('FACULTY');
            $teachers = collect([$fallback]);
        }

        // Create 2-4 grades per enrollment (CC, EXAM, TP, etc.)
        $enrollments->each(function (CourseEnrollment $enrollment) use ($teachers) {
            $studentId = $enrollment->enrollment?->student_id;
            if (! $studentId) {
                return;
            }

            $academicYearName = $enrollment->academicYear?->name
                ?? $enrollment->enrollment?->academicYear?->name
                ?? $this->currentAcademicYearName();
            $semester = $enrollment->semester ?? $enrollment->enrollment?->current_semester ?? 1;
            [$examStart, $examEnd] = $this->examWindow($academicYearName, (int) $semester);

            $numberOfGrades = rand(2, 4);
            $gradeTypes = collect([GradeType::CC, GradeType::EXAM, GradeType::TP, GradeType::ORAL]);
            $selectedTypes = $gradeTypes->random(min($numberOfGrades, $gradeTypes->count()));

            foreach ($selectedTypes as $type) {
                $maxScore = 20.00; // Standard French grading scale
                $score = fake()->randomFloat(2, 8.00, 20.00); // Realistic student scores
                $enteredAt = Carbon::instance(fake()->dateTimeBetween($examStart, $examEnd));
                $status = fake()->randomElement([GradeStatus::PUBLISHED, GradeStatus::VALIDATED, GradeStatus::VALIDATED, GradeStatus::SUBMITTED]);
                $validatedAt = in_array($status, [GradeStatus::VALIDATED, GradeStatus::PUBLISHED], true)
                    ? $enteredAt->copy()->addDays(rand(0, 5))
                    : null;
                if ($validatedAt && $validatedAt->greaterThan($examEnd)) {
                    $validatedAt = $examEnd->copy()->setTime(17, 0);
                }

                Grade::create([
                    'course_enrollment_id' => $enrollment->id,
                    'student_id' => $studentId,
                    'course_id' => $enrollment->course_id,
                    'type' => $type,
                    'score' => $score,
                    'max_score' => $maxScore,
                    'weight' => $this->getWeightForType($type),
                    'entered_by' => $teachers->random()->id,
                    'status' => $status,
                    'entered_at' => $enteredAt,
                    'validated_at' => $validatedAt,
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
