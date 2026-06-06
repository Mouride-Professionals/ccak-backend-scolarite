<?php

namespace Database\Seeders;

use App\Models\FacultyContract;
use App\Models\FacultyMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FacultyContractSeeder extends Seeder
{
    public function run(): void
    {
        $facultyMembers = FacultyMember::all();
        if ($facultyMembers->isEmpty()) {
            $this->call(FacultyMemberSeeder::class);
            $facultyMembers = FacultyMember::all();
        }

        foreach ($facultyMembers as $member) {
            $startDate = $member->hire_date ?? Carbon::now()->subYears(3)->toDateString();
            $endDate = $member->contract_type === 'TEMPORARY'
                ? Carbon::parse($startDate)->addYear()->toDateString()
                : null;

            FacultyContract::firstOrCreate(
                [
                    'faculty_member_id' => $member->id,
                    'is_current' => true,
                ],
                [
                    'contract_type' => $member->contract_type ?? 'PERMANENT',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'salary' => $member->contract_type === 'HOURLY' ? null : 350000,
                    'terms' => 'Contrat standard UCAK.',
                    'file_path' => 'faculty_contracts/'.$member->id.'/contract.pdf',
                    'file_name' => 'contract_'.$member->staff_number.'.pdf',
                    'status' => FacultyContract::STATUS_ACTIVE,
                    'is_current' => true,
                ]
            );
        }
    }
}
