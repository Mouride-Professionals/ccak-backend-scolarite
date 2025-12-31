<?php

namespace Database\Seeders;

use App\Models\FacultyMember;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FacultyMemberSeeder extends Seeder
{
    use UsesSenegalAcademicCalendar;

    public function run(): void
    {
        $departments = Department::all();
        if ($departments->isEmpty()) {
            $this->call(DepartmentSeeder::class);
            $departments = Department::all();
        }

        $currentYear = $this->currentAcademicYearName();
        [$startYear] = $this->parseAcademicYear($currentYear);

        $rows = [
            [
                'staff_number' => 'FM-001',
                'full_name' => 'Prof. Awa Ndiaye',
                'phone' => '776123456',
                'address' => 'Dakar',
                'department_code' => 'INF',
                'rank' => 'PROFESSEUR',
                'contract_type' => 'PERMANENT',
                'hire_date' => Carbon::create($startYear - 12, 10, 1, 0, 0, 0, 'Africa/Dakar')->toDateString(),
                'is_active' => true,
            ],
            [
                'staff_number' => 'FM-002',
                'full_name' => 'Dr. Abdoulaye Diop',
                'phone' => '770654321',
                'address' => 'Thies',
                'department_code' => 'MAT',
                'rank' => 'MAITRE_CONF',
                'contract_type' => 'PERMANENT',
                'hire_date' => Carbon::create($startYear - 7, 10, 1, 0, 0, 0, 'Africa/Dakar')->toDateString(),
                'is_active' => true,
            ],
            [
                'staff_number' => 'FM-003',
                'full_name' => 'Mme. Mariama Sow',
                'phone' => '781112233',
                'address' => 'Saint-Louis',
                'department_code' => 'DRP',
                'rank' => 'ASSISTANT',
                'contract_type' => 'TEMPORARY',
                'hire_date' => Carbon::create($startYear - 3, 10, 1, 0, 0, 0, 'Africa/Dakar')->toDateString(),
                'is_active' => true,
            ],
            [
                'staff_number' => 'FM-004',
                'full_name' => 'Dr. Sokhna Ba',
                'phone' => '771234567',
                'address' => 'Kaolack',
                'department_code' => 'ECO',
                'rank' => 'MAITRE_CONF',
                'contract_type' => 'PERMANENT',
                'hire_date' => Carbon::create($startYear - 5, 10, 1, 0, 0, 0, 'Africa/Dakar')->toDateString(),
                'is_active' => true,
            ],
            [
                'staff_number' => 'FM-005',
                'full_name' => 'Dr. Ousmane Sarr',
                'phone' => '765432198',
                'address' => 'Ziguinchor',
                'department_code' => 'SPU',
                'rank' => 'MAITRE_CONF',
                'contract_type' => 'PERMANENT',
                'hire_date' => Carbon::create($startYear - 6, 10, 1, 0, 0, 0, 'Africa/Dakar')->toDateString(),
                'is_active' => true,
            ],
        ];

        foreach ($rows as $row) {
            $department = $departments->firstWhere('code', $row['department_code']);
            $email = $this->emailFromName($row['full_name']);
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'password' => bcrypt('password'),
                    'email_verified_at' => Carbon::now('Africa/Dakar'),
                    'remember_token' => Str::random(10),
                ]
            );
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('FACULTY');
            }

            FacultyMember::firstOrCreate(
                ['staff_number' => $row['staff_number']],
                [
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'full_name' => $row['full_name'],
                    'phone' => $row['phone'],
                    'address' => $row['address'],
                    'department_id' => $department?->id,
                    'rank' => $row['rank'],
                    'contract_type' => $row['contract_type'],
                    'hire_date' => $row['hire_date'],
                    'is_active' => $row['is_active'],
                ]
            );
        }
    }

    private function emailFromName(string $fullName): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $fullName));
        $slug = trim($slug ?? '', '.');
        return $slug . '@ucak.sn';
    }
}
