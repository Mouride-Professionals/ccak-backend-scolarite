<?php

namespace Database\Seeders;

use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\DeliberationSession;
use App\Models\FacultyMember;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DeliberationSessionSeeder extends Seeder
{
    use UsesSenegalAcademicCalendar;

    public function run(): void
    {
        $facultyMembers = FacultyMember::all();
        if ($facultyMembers->isEmpty()) {
            $this->call(FacultyMemberSeeder::class);
            $facultyMembers = FacultyMember::all();
        }

        if ($facultyMembers->count() < 3) {
            $this->command->warn('Not enough faculty members for deliberation sessions.');

            return;
        }

        $program = AcademicProgram::query()->inRandomOrder()->first();
        if (! $program) {
            $this->call(AcademicProgramSeeder::class);
            $program = AcademicProgram::query()->inRandomOrder()->first();
        }

        if (! $program) {
            $this->command->warn('No academic program found. Skipping deliberation sessions.');

            return;
        }

        $academicYears = AcademicYear::all();
        if ($academicYears->isEmpty()) {
            $this->call(AcademicYearSeeder::class);
            $academicYears = AcademicYear::all();
        }

        $currentYearName = $this->currentAcademicYearName();
        $academicYear = $academicYears->firstWhere('name', $currentYearName)
            ?? $academicYears->sortByDesc('name')->first();
        if (! $academicYear) {
            $this->command->warn('No academic year found. Skipping deliberation sessions.');

            return;
        }

        $facultyMembers = $facultyMembers->values();

        foreach ([1, 2] as $semester) {
            [$delibStart, $delibEnd] = $this->deliberationWindow($academicYear->name, $semester);
            $sessionDate = Carbon::instance(fake()->dateTimeBetween($delibStart, $delibEnd));
            $president = $facultyMembers[($semester - 1) % $facultyMembers->count()];
            $juryMembers = $facultyMembers->reject(fn (FacultyMember $member) => $member->id === $president->id)->take(2);

            $session = DeliberationSession::firstOrCreate(
                [
                    'academic_program_id' => $program->id,
                    'academic_year_id' => $academicYear->id,
                    'semester' => $semester,
                ],
                [
                    'session_name' => sprintf('Session de deliberation S%d %s', $semester, $academicYear->name),
                    'session_date' => $sessionDate->toDateString(),
                    'status' => DeliberationSession::STATUS_COMPLETED,
                    'presided_by' => $president->id,
                    'jury_members' => null,
                    'created_at' => $sessionDate,
                    'updated_at' => $sessionDate,
                ]
            );

            $session->juryMembers()->sync($juryMembers->pluck('id')->all());
        }
    }
}
