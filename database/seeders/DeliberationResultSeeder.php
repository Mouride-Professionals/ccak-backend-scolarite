<?php

namespace Database\Seeders;

use App\Models\DeliberationResult;
use App\Models\DeliberationSession;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DeliberationResultSeeder extends Seeder
{
    public function run(): void
    {
        $sessions = DeliberationSession::all();

        if ($sessions->isEmpty()) {
            $this->command->warn('⚠️  No DeliberationSessions found. Creating sample sessions...');
            $this->call(DeliberationSessionSeeder::class);
            $sessions = DeliberationSession::all();
        }

        $students = Student::all();

        if ($students->isEmpty()) {
            $this->command->warn('⚠️  No Students found. Creating sample students...');
            $this->call(StudentSeeder::class);
            $students = Student::all();
        }

        $decisions = DeliberationResult::DECISIONS;
        $honorLevels = DeliberationResult::HONOR_LEVELS;
        $totalResults = 0;

        foreach ($sessions as $session) {
            $numberOfResults = rand(10, 20);
            $sessionStudents = $students->random(min($numberOfResults, $students->count()));

            foreach ($sessionStudents as $student) {
                $isWithHonors = rand(0, 1);

                DeliberationResult::create([
                    'deliberation_session_id' => $session->id,
                    'student_id' => $student->id,
                    'decision' => $decisions[array_rand($decisions)],
                    'jury_remarks' => 'Remarks for ' . $student->full_name,
                    'is_with_honors' => $isWithHonors,
                    'honor_level' => $isWithHonors ? $honorLevels[array_rand($honorLevels)] : null,
                ]);

                $totalResults++;
            }
        }

        $this->command->info("✅ Created {$totalResults} DeliberationResults across {$sessions->count()} sessions");
    }
}
