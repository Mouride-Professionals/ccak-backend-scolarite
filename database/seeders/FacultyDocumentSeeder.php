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
                ['type' => FacultyDocument::TYPE_CV, 'file_name' => 'cv_' . $member->staff_number . '.pdf'],
                ['type' => FacultyDocument::TYPE_DIPLOMA, 'file_name' => 'diploma_' . $member->staff_number . '.pdf'],
                ['type' => FacultyDocument::TYPE_CNI, 'file_name' => 'cni_' . $member->staff_number . '.pdf'],
            ];

            foreach ($documents as $doc) {
                FacultyDocument::firstOrCreate(
                    [
                        'faculty_member_id' => $member->id,
                        'type' => $doc['type'],
                    ],
                    [
                        'file_path' => 'faculty_documents/' . $member->id . '/' . $doc['file_name'],
                        'file_name' => $doc['file_name'],
                        'status' => FacultyDocument::STATUS_APPROVED,
                    ]
                );
            }
        }
    }
}
