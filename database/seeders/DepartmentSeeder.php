<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $faculties = Faculty::all();
        if ($faculties->isEmpty()) {
            $this->call(FacultySeeder::class);
            $faculties = Faculty::all();
        }

        $departments = [
            'FST' => [
                ['code' => 'INF', 'name' => 'Informatique'],
                ['code' => 'MAT', 'name' => 'Mathematiques'],
                ['code' => 'PHY', 'name' => 'Physique'],
                ['code' => 'CHI', 'name' => 'Chimie'],
                ['code' => 'BIO', 'name' => 'Biologie'],
            ],
            'FLSH' => [
                ['code' => 'LDM', 'name' => 'Lettres Modernes'],
                ['code' => 'HIS', 'name' => 'Histoire'],
                ['code' => 'GEO', 'name' => 'Geographie'],
                ['code' => 'PHI', 'name' => 'Philosophie'],
            ],
            'FSJP' => [
                ['code' => 'DRP', 'name' => 'Droit Public'],
                ['code' => 'DRI', 'name' => 'Droit Prive'],
                ['code' => 'POL', 'name' => 'Sciences Politiques'],
            ],
            'FSEG' => [
                ['code' => 'ECO', 'name' => 'Economie'],
                ['code' => 'GES', 'name' => 'Gestion'],
                ['code' => 'COM', 'name' => 'Comptabilite'],
            ],
            'FSS' => [
                ['code' => 'MED', 'name' => 'Medecine'],
                ['code' => 'PHA', 'name' => 'Pharmacie'],
                ['code' => 'SPU', 'name' => 'Sante Publique'],
            ],
        ];

        foreach ($departments as $facultyCode => $rows) {
            $faculty = $faculties->firstWhere('code', $facultyCode);
            if (! $faculty) {
                continue;
            }

            foreach ($rows as $row) {
                Department::firstOrCreate(
                    [
                        'faculty_id' => $faculty->id,
                        'code' => $row['code'],
                    ],
                    [
                        'name' => $row['name'],
                        'head_id' => null,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
