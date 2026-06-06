<?php

namespace Database\Seeders;

use App\Models\AcademicProgram;
use App\Models\Department;
use Illuminate\Database\Seeder;

class AcademicProgramSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::all();
        if ($departments->isEmpty()) {
            $this->call(DepartmentSeeder::class);
            $departments = Department::all();
        }

        $programs = [
            'INF' => [
                ['name' => 'Licence Informatique', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
                ['name' => 'Master Informatique', 'level' => AcademicProgram::LEVELS[1], 'duration' => 4, 'credits' => 120],
            ],
            'MAT' => [
                ['name' => 'Licence Mathematiques', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
                ['name' => 'Master Mathematiques', 'level' => AcademicProgram::LEVELS[1], 'duration' => 4, 'credits' => 120],
            ],
            'PHY' => [
                ['name' => 'Licence Physique', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
                ['name' => 'Master Physique', 'level' => AcademicProgram::LEVELS[1], 'duration' => 4, 'credits' => 120],
            ],
            'CHI' => [
                ['name' => 'Licence Chimie', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ],
            'BIO' => [
                ['name' => 'Licence Biologie', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ],
            'LDM' => [
                ['name' => 'Licence Lettres Modernes', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ],
            'HIS' => [
                ['name' => 'Licence Histoire', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ],
            'GEO' => [
                ['name' => 'Licence Geographie', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ],
            'PHI' => [
                ['name' => 'Licence Philosophie', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ],
            'DRP' => [
                ['name' => 'Licence Droit Public', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
                ['name' => 'Master Droit Public', 'level' => AcademicProgram::LEVELS[1], 'duration' => 4, 'credits' => 120],
            ],
            'DRI' => [
                ['name' => 'Licence Droit Prive', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
                ['name' => 'Master Droit Prive', 'level' => AcademicProgram::LEVELS[1], 'duration' => 4, 'credits' => 120],
            ],
            'POL' => [
                ['name' => 'Licence Sciences Politiques', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ],
            'ECO' => [
                ['name' => 'Licence Economie', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
                ['name' => 'Master Economie', 'level' => AcademicProgram::LEVELS[1], 'duration' => 4, 'credits' => 120],
            ],
            'GES' => [
                ['name' => 'Licence Gestion', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
                ['name' => 'Master Gestion', 'level' => AcademicProgram::LEVELS[1], 'duration' => 4, 'credits' => 120],
            ],
            'COM' => [
                ['name' => 'Licence Comptabilite', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ],
            'MED' => [
                ['name' => 'Doctorat Medecine', 'level' => AcademicProgram::LEVELS[2], 'duration' => 12, 'credits' => 360],
            ],
            'PHA' => [
                ['name' => 'Doctorat Pharmacie', 'level' => AcademicProgram::LEVELS[2], 'duration' => 12, 'credits' => 360],
            ],
            'SPU' => [
                ['name' => 'Master Sante Publique', 'level' => AcademicProgram::LEVELS[1], 'duration' => 4, 'credits' => 120],
            ],
        ];

        foreach ($programs as $deptCode => $rows) {
            $department = $departments->firstWhere('code', $deptCode);
            if (! $department) {
                continue;
            }

            foreach ($rows as $row) {
                AcademicProgram::firstOrCreate(
                    [
                        'department_id' => $department->id,
                        'name' => $row['name'],
                    ],
                    [
                        'level' => $row['level'],
                        'duration_semesters' => $row['duration'],
                        'total_credits_required' => $row['credits'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
