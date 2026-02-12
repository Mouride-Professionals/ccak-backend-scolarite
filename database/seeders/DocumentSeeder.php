<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Document;
use App\Models\Admin;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
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
                'file_name' => fake()->word() . '.pdf',
                'media_id' => null,
                'notes' => $notes,
                'uploaded_at' => $uploadedAt,
                'reviewed_at' => $reviewedAt,
                'created_at' => $uploadedAt,
                'updated_at' => $reviewedAt ?? $uploadedAt,
            ]);

            $this->attachDocumentMedia($document, $document->type?->value ?? (string) $document->type);
        }
    }

    private function attachDocumentMedia(Document $document, string $type): void
    {
        $extension = $type === 'PHOTO' ? 'png' : 'pdf';
        $fileName = "{$type}_{$document->student_id}.{$extension}";
        $tmpPath = tempnam(sys_get_temp_dir(), 'doc_seed_');
        if ($tmpPath === false) {
            return;
        }

        $tmpFile = $tmpPath . '.' . $extension;
        rename($tmpPath, $tmpFile);

        if ($extension === 'png') {
            $png = base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg=='
            );
            file_put_contents($tmpFile, $png ?: '');
        } else {
            $pdf = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
            file_put_contents($tmpFile, $pdf);
        }

        try {
            $media = $document->addMedia($tmpFile)
                ->usingFileName($fileName)
                ->usingName($type)
                ->toMediaCollection($type);

            $document->update([
                'media_id' => $media->id,
                'file_path' => $media->getPathRelativeToRoot(),
                'file_name' => $media->file_name,
            ]);
        } finally {
            @unlink($tmpFile);
        }
    }
}
