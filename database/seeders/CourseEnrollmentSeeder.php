<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CourseEnrollment;
use App\Models\Course;
use App\Models\Enrollment;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Support\Carbon;

class CourseEnrollmentSeeder extends Seeder
{
    use UsesSenegalAcademicCalendar;

    public function run(): void
    {
        $enrollments = Enrollment::with('academicYear')->get();
        $courses = Course::all();

        if ($enrollments->isEmpty()) {
            $this->call(EnrollmentSeeder::class);
            $enrollments = Enrollment::with('academicYear')->get();
        }

        if ($courses->isEmpty()) {
            $this->call(CourseSeeder::class);
            $courses = Course::all();
        }

        if ($enrollments->isEmpty() || $courses->isEmpty()) {
            $this->command->warn('No enrollments or courses found. Skipping course enrollments.');
            return;
        }

        $enrollments->each(function (Enrollment $enrollment) use ($courses): void {
            $numberOfCourses = rand(4, 6);
            $selectedCourses = $courses->random(min($numberOfCourses, $courses->count()));
            $academicYearName = $enrollment->academicYear?->name ?? $this->currentAcademicYearName();
            $semester = $enrollment->current_semester ?? 1;
            [$semesterStart, $semesterEnd] = $this->semesterWindow($academicYearName, $semester);
            $enrollmentDate = Carbon::instance(fake()->dateTimeBetween($semesterStart->copy()->addWeeks(1), $semesterEnd->copy()->subWeeks(6)));

            foreach ($selectedCourses as $course) {
                $status = fake()->randomElement([
                    CourseEnrollment::STATUS_ENROLLED,
                    CourseEnrollment::STATUS_ENROLLED,
                    CourseEnrollment::STATUS_COMPLETED,
                    CourseEnrollment::STATUS_DROPPED,
                ]);
                $dropDate = $status === CourseEnrollment::STATUS_DROPPED
                    ? $enrollmentDate->copy()->addWeeks(rand(2, 6))->toDateString()
                    : null;

                CourseEnrollment::firstOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'course_id' => $course->id,
                        'academic_year_id' => $enrollment->academic_year_id,
                    ],
                    [
                        'semester' => $semester,
                        'status' => $status,
                        'enrollment_date' => $enrollmentDate->toDateString(),
                        'drop_date' => $dropDate,
                    ]
                );
            }
        });

        $this->command->info('Course enrollments created successfully.');
    }
}
