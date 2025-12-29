<?php

namespace Database\Seeders;

use App\Models\FacultyMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FacultyMemberSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'id' => (string) Str::uuid(),
                'user_id' => null,           // assignez un user_id existant si besoin
                'staff_number' => 'FM-001',
                'full_name' => 'Prof. Alice Dupont',
                'phone' => '0600000001',
                'address' => 'Campus A',
                'department_id' => null,     // assignez un department_id existant si besoin
                'rank' => 'PROFESSEUR',
                'contract_type' => 'PERMANENT',
                'hire_date' => now()->subYears(8),
                'is_active' => true,
            ],
            [
                'id' => (string) Str::uuid(),
                'user_id' => null,
                'staff_number' => 'FM-002',
                'full_name' => 'Dr. Bob Martin',
                'phone' => '0600000002',
                'address' => 'Campus B',
                'department_id' => null,
                'rank' => 'MAITRE_CONF',
                'contract_type' => 'PERMANENT',
                'hire_date' => now()->subYears(5),
                'is_active' => true,
            ],
            [
                'id' => (string) Str::uuid(),
                'user_id' => null,
                'staff_number' => 'FM-003',
                'full_name' => 'Mme. Claire Leroy',
                'phone' => '0600000003',
                'address' => 'Campus C',
                'department_id' => null,
                'rank' => 'ASSISTANT',
                'contract_type' => 'TEMPORARY',
                'hire_date' => now()->subYears(2),
                'is_active' => true,
            ],
        ];

        foreach ($rows as $row) {
            FacultyMember::firstOrCreate(
                ['staff_number' => $row['staff_number']],
                $row
            );
        }
    }
}
