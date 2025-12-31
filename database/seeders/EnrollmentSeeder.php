<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Student;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Support\Carbon;

class EnrollmentSeeder extends Seeder
{
    use UsesSenegalAcademicCalendar;

    public function run(): void
    {
        $students = Student::all();
        $programs = AcademicProgram::all();
        $academicYears = AcademicYear::all();

        if ($students->isEmpty()) {
            $this->call(StudentSeeder::class);
            $students = Student::all();
        }

        if ($programs->isEmpty()) {
            $this->call(AcademicProgramSeeder::class);
            $programs = AcademicProgram::all();
        }

        if ($academicYears->isEmpty()) {
            $this->call(AcademicYearSeeder::class);
            $academicYears = AcademicYear::all();
        }

        $currentYearName = $this->currentAcademicYearName();
        $currentYear = $academicYears->firstWhere('name', $currentYearName) ?? $academicYears->sortByDesc('name')->first();
        $currentYearName = $currentYear?->name ?? $currentYearName;
        [$enrollStart, $enrollEnd] = $this->enrollmentWindow($currentYearName);
        $now = Carbon::now('Africa/Dakar');
        $currentSemester = $now->greaterThanOrEqualTo($this->semesterWindow($currentYearName, 2)[0]) ? 2 : 1;

        $students->each(function (Student $student) use ($programs, $currentYear, $enrollStart, $enrollEnd, $currentSemester): void {
            $enrollmentDate = Carbon::instance(fake()->dateTimeBetween($enrollStart, $enrollEnd));
            $status = fake()->randomElement(['REGISTERED', 'ACTIVE', 'ACTIVE', 'ACTIVE']);
            $isScholarship = fake()->boolean(20);

            Enrollment::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_program_id' => $programs->random()->id,
                    'academic_year_id' => $currentYear->id,
                ],
                [
                    'current_semester' => $currentSemester,
                    'status' => $status,
                    'enrollment_date' => $enrollmentDate->toDateString(),
                    'registration_fee_paid' => $isScholarship
                        ? fake()->randomFloat(2, 0, 15000)
                        : fake()->randomFloat(2, 20000, 60000),
                    'is_scholarship' => $isScholarship,
                ]
            );
        });
    }
}
