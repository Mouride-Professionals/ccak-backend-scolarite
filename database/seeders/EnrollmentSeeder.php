<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Enums\RegistrationStatus;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Student;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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

        $levels = Level::all();
        if ($levels->isEmpty()) {
            $this->call(CcakReferentialSeeder::class);
            $levels = Level::all();
        }

        $students->each(function (Student $student) use ($programs, $currentYear, $enrollStart, $enrollEnd, $currentSemester, $levels): void {
            $enrollmentDate = Carbon::instance(fake()->dateTimeBetween($enrollStart, $enrollEnd));
            $status = fake()->randomElement([
                RegistrationStatus::PENDING_VALIDATION->value,
                RegistrationStatus::VALIDATED->value,
                RegistrationStatus::VALIDATED->value,
                RegistrationStatus::VALIDATED->value,
                RegistrationStatus::SUSPENDED->value,
            ]);
            $isScholarship = fake()->boolean(20);
            $program = $programs->random();

            // Pick a level whose degree_cycle type matches the program level
            $level = $levels->isNotEmpty() ? $levels->random() : null;

            Enrollment::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_program_id' => $program->id,
                    'academic_year_id' => $currentYear->id,
                ],
                [
                    'level_id' => $level?->id,
                    'current_semester' => $currentSemester,
                    'status' => $status,
                    'enrollment_date' => $enrollmentDate->toDateString(),
                    'registration_number' => strtoupper(fake()->bothify('INS-####-??')),
                    'registration_fee_paid' => $isScholarship
                        ? fake()->randomFloat(2, 0, 15000)
                        : fake()->randomFloat(2, 20000, 60000),
                    'is_scholarship_holder' => $isScholarship,
                    'scholarship_type' => $isScholarship ? fake()->randomElement(['Bourse nationale', 'Bourse excellence', 'Aide sociale']) : null,
                    'scholarship_amount' => $isScholarship ? fake()->randomFloat(2, 50000, 300000) : null,
                    'is_repeating' => fake()->boolean(15),
                    'is_medically_fit' => fake()->boolean(90),
                    'is_registered_elsewhere' => fake()->boolean(5),
                    'is_willing_to_cancel_other_registration' => false,
                    'notes' => fake()->boolean(20) ? fake()->sentence() : null,
                ]
            );
        });
    }
}
