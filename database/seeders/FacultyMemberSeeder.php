<?php

namespace Database\Seeders;

use App\Models\FacultyMember;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FacultyMemberSeeder extends Seeder
{
    use UsesSenegalAcademicCalendar;

    public function run(): void
    {
        $currentYear = $this->currentAcademicYearName();
        [$startYear] = $this->parseAcademicYear($currentYear);

        $rows = [
            [
                'user_id' => null,           // assignez un user_id existant si besoin
                'staff_number' => 'FM-001',
                'full_name' => 'Prof. Awa Ndiaye',
                'phone' => '776123456',
                'address' => 'Dakar',
                'department_id' => null,     // assignez un department_id existant si besoin
                'rank' => 'PROFESSEUR',
                'contract_type' => 'PERMANENT',
                'hire_date' => Carbon::create($startYear - 12, 10, 1, 0, 0, 0, 'Africa/Dakar')->toDateString(),
                'is_active' => true,
            ],
            [
                'user_id' => null,
                'staff_number' => 'FM-002',
                'full_name' => 'Dr. Abdoulaye Diop',
                'phone' => '770654321',
                'address' => 'Thies',
                'department_id' => null,
                'rank' => 'MAITRE_CONF',
                'contract_type' => 'PERMANENT',
                'hire_date' => Carbon::create($startYear - 7, 10, 1, 0, 0, 0, 'Africa/Dakar')->toDateString(),
                'is_active' => true,
            ],
            [
                'user_id' => null,
                'staff_number' => 'FM-003',
                'full_name' => 'Mme. Mariama Sow',
                'phone' => '781112233',
                'address' => 'Saint-Louis',
                'department_id' => null,
                'rank' => 'ASSISTANT',
                'contract_type' => 'TEMPORARY',
                'hire_date' => Carbon::create($startYear - 3, 10, 1, 0, 0, 0, 'Africa/Dakar')->toDateString(),
                'is_active' => true,
            ],
        ];

        foreach ($rows as $row) {
            FacultyMember::firstOrCreate(
                ['staff_number' => $row['staff_number']],
                array_merge($row, ['id' => (string) Str::uuid()])
            );
        }
    }
}
