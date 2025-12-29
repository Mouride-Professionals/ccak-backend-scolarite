<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\GeneratedDocument;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class GeneratedDocumentSeeder extends Seeder
{
    use UsesSenegalAcademicCalendar;

    public function run(): void
    {
        $students = Student::all();
        $users = User::all();

        if ($students->isEmpty()) {
            $this->call(StudentSeeder::class);
            $students = Student::all();
        }

        if ($users->isEmpty()) {
            $users = User::factory()->count(5)->create();
        }

        if ($students->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Missing students or users. Skipping generated documents.');
            return;
        }

        $academicYearName = $this->currentAcademicYearName();
        [$issueStart, $issueEnd] = $this->documentIssuanceWindow($academicYearName);

        for ($i = 0; $i < 20; $i++) {
            $student = $students->random();
            $status = fake()->randomElement([
                GeneratedDocument::STATUS_DRAFT,
                GeneratedDocument::STATUS_ISSUED,
                GeneratedDocument::STATUS_ISSUED,
                GeneratedDocument::STATUS_REVOKED,
            ]);
            $generatedAt = Carbon::instance(fake()->dateTimeBetween($issueStart, $issueEnd));
            $issuedAt = null;

            if ($status !== GeneratedDocument::STATUS_DRAFT) {
                $issuedAt = $generatedAt->copy()->addDays(rand(1, 7));
                if ($issuedAt->greaterThan($issueEnd)) {
                    $issuedAt = $issueEnd->copy();
                }
            }

            GeneratedDocument::create([
                'student_id' => $student->id,
                'type' => fake()->randomElement(GeneratedDocument::getTypes()),
                'document_number' => sprintf('UCAK-%s-%s', str_replace('-', '', $academicYearName), strtoupper(Str::random(6))),
                'file_path' => 'generated/' . Str::uuid() . '.pdf',
                'generated_by' => $users->random()->id,
                'metadata' => ['source' => 'seed'],
                'generated_at' => $generatedAt,
                'issued_at' => $issuedAt,
                'status' => $status,
                'created_at' => $generatedAt,
                'updated_at' => $issuedAt ?? $generatedAt,
            ]);
        }
    }
}
