<?php

namespace Database\Seeders;

use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\DeliberationSession;
use App\Models\FacultyMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeliberationSessionSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Facultés (président + jury)
        $faculty = collect([
            [
                'id' => '27643d9d-7f7a-47d7-8e1d-2cd258de54ae',
                'staff_number' => 'FM-001',
                'full_name' => 'Prof. Alice Dupont',
                'rank' => 'PROFESSEUR',
                'contract_type' => 'PERMANENT',
                'hire_date' => '2017-12-28',
            ],
            [
                'id' => '90cb14ef-f22c-49d5-bd37-bb3c492b415c',
                'staff_number' => 'FM-002',
                'full_name' => 'Dr. Bob Martin',
                'rank' => 'MAITRE_CONF',
                'contract_type' => 'PERMANENT',
                'hire_date' => '2020-12-28',
            ],
            [
                'id' => '64b18032-d3b2-4d91-b2e4-bd779059e49a',
                'staff_number' => 'FM-003',
                'full_name' => 'Mme. Claire Leroy',
                'rank' => 'ASSISTANT',
                'contract_type' => 'TEMPORARY',
                'hire_date' => '2023-12-28',
            ],
        ])->map(function ($row) {
            return FacultyMember::firstOrCreate(
                ['staff_number' => $row['staff_number']],
                array_merge($row, [
                    'user_id' => null,
                    'phone' => null,
                    'address' => null,
                    'department_id' => null,
                    'is_active' => true,
                ])
            );
        });

        // 2) Academic Program & Year (existants ou créés à la volée)
        $programId = AcademicProgram::query()->value('id')
            ?? AcademicProgram::factory()->create()->id;

        $yearId = AcademicYear::query()->value('id')
            ?? AcademicYear::factory()->create()->id;

        // 3) Créer la session de délibération
        $session = DeliberationSession::query()->firstOrCreate(
            ['id' => (string) Str::uuid()],
            [
                'academic_program_id' => $programId,
                'academic_year_id' => $yearId,
                'semester' => 1,
                'session_name' => 'Session de délibération S1',
                'session_date' => now()->toDateString(),
                'status' => DeliberationSession::STATUS_SCHEDULED,
                'presided_by' => '27643d9d-7f7a-47d7-8e1d-2cd258de54ae', // Prof. Alice Dupont
                'jury_members' => null, // conservé si la colonne existe, mais non utilisée pour la relation
            ]
        );

        // 4) Lier les membres du jury via le pivot
        $session->juryMembers()->sync([
            '90cb14ef-f22c-49d5-bd37-bb3c492b415c', // Dr. Bob Martin
            '64b18032-d3b2-4d91-b2e4-bd779059e49a', // Mme. Claire Leroy
        ]);
    }
}
