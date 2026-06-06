<?php

namespace Database\Seeders;

use App\Models\FacultyDocument;
use App\Models\FacultyMember;
use Illuminate\Database\Seeder;

class FacultyDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $facultyMembers = FacultyMember::all();
        if ($facultyMembers->isEmpty()) {
            $this->call(FacultyMemberSeeder::class);
            $facultyMembers = FacultyMember::all();
        }

        foreach ($facultyMembers as $member) {
            $documents = [
                ['type' => FacultyDocument::TYPE_CV, 'file_name' => 'cv_'.$member->staff_number.'.pdf'],
                ['type' => FacultyDocument::TYPE_DIPLOMA, 'file_name' => 'diploma_'.$member->staff_number.'.pdf'],
                ['type' => FacultyDocument::TYPE_CNI, 'file_name' => 'cni_'.$member->staff_number.'.pdf'],
            ];

            foreach ($documents as $doc) {
                $facultyDocument = FacultyDocument::firstOrCreate(
                    [
                        'faculty_member_id' => $member->id,
                        'type' => $doc['type'],
                    ],
                    [
                        'file_path' => '',
                        'file_name' => $doc['file_name'],
                        'media_id' => null,
                        'status' => FacultyDocument::STATUS_APPROVED,
                    ]
                );

                $this->attachFacultyMedia($facultyDocument, $doc['type'], $doc['file_name']);
            }
        }
    }

    private function attachFacultyMedia(FacultyDocument $document, string $type, string $fileName): void
    {
        $extension = $type === FacultyDocument::TYPE_CNI ? 'png' : 'pdf';
        $tmpPath = tempnam(sys_get_temp_dir(), 'fac_doc_');
        if ($tmpPath === false) {
            return;
        }

        $tmpFile = $tmpPath.'.'.$extension;
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
