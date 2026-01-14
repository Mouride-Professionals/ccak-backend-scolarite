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
                'file_path' => '',
                'generated_by' => $users->random()->id,
                'metadata' => ['source' => 'seed'],
                'generated_at' => $generatedAt,
                'issued_at' => $issuedAt,
                'status' => $status,
                'created_at' => $generatedAt,
                'updated_at' => $issuedAt ?? $generatedAt,
            ])->tap(function (GeneratedDocument $document): void {
                $this->attachGeneratedMedia($document);
            });
        }
    }

    private function attachGeneratedMedia(GeneratedDocument $document): void
    {
        $fileName = $document->document_number . '.pdf';
        $tmpPath = tempnam(sys_get_temp_dir(), 'gen_doc_');
        if ($tmpPath === false) {
            return;
        }

        $tmpFile = $tmpPath . '.pdf';
        rename($tmpPath, $tmpFile);
        $pdf = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
        file_put_contents($tmpFile, $pdf);

        try {
            $media = $document->addMedia($tmpFile)
                ->usingFileName($fileName)
                ->usingName($document->document_number)
                ->toMediaCollection('official_documents');

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
