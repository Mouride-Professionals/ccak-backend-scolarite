<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Document;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DocumentSeeder extends Seeder
{
    use UsesSenegalAcademicCalendar;

    public function run(): void
    {
        $students = Student::all();
        if ($students->isEmpty()) {
            $this->call(StudentSeeder::class);
            $students = Student::all();
        }

        if ($students->isEmpty()) {
            $this->command->warn('No students found. Skipping documents.');

            return;
        }

        $reviewers = Admin::all();
        if ($reviewers->isEmpty()) {
            $this->call(AdminSeeder::class);
            $reviewers = Admin::all();
        }
        if ($reviewers->isEmpty()) {
            $reviewerUser = User::factory()->create([
                'email' => 'admin.seed@ucak.sn',
                'email_verified_at' => Carbon::now('Africa/Dakar'),
            ]);
            $reviewerUser->assignRole('ADMIN');
            $reviewers = collect([
                Admin::create([
                    'user_id' => $reviewerUser->id,
                    'full_name' => $reviewerUser->email,
                ]),
            ]);
        }

        $academicYearName = $this->currentAcademicYearName();
        [$uploadStart, $uploadEnd] = $this->enrollmentWindow($academicYearName);
        $documentTypes = array_keys(Document::typeLabels());
        $targetCount = min(40, max(12, $students->count() * 2));

        for ($i = 0; $i < $targetCount; $i++) {
            $student = $students->random();
            $uploadedAt = Carbon::instance(fake()->dateTimeBetween($uploadStart, $uploadEnd));
            $status = fake()->randomElement(['PENDING', 'APPROVED', 'REJECTED']);
            $reviewedBy = null;
            $reviewedAt = null;
            $notes = null;

            if ($status !== 'PENDING' && $reviewers->isNotEmpty()) {
                $reviewedBy = $reviewers->random()->id;
                $reviewedAt = $uploadedAt->copy()->addDays(rand(2, 21));
                if ($reviewedAt->greaterThan($uploadEnd->copy()->addWeeks(4))) {
                    $reviewedAt = $uploadEnd->copy()->addWeeks(4);
                }
                if ($status === 'REJECTED') {
                    $notes = fake()->sentence();
                }
            } elseif ($status !== 'PENDING') {
                $status = 'PENDING';
            }

            $document = Document::create([
                'student_id' => $student->id,
                'reviewed_by' => $reviewedBy,
                'type' => $documentTypes[array_rand($documentTypes)],
                'status' => $status,
                'file_path' => '',
                'file_name' => fake()->word().'.pdf',
                'media_id' => null,
                'notes' => $notes,
                'uploaded_at' => $uploadedAt,
                'reviewed_at' => $reviewedAt,
                'created_at' => $uploadedAt,
                'updated_at' => $reviewedAt ?? $uploadedAt,
            ]);

        }
    }
}
