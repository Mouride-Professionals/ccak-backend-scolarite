<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

class FacultySeeder extends Seeder
{
    public function run(): void
    {
        $faculties = [
            ['code' => 'FST', 'name' => 'Faculte des Sciences et Technologies'],
            ['code' => 'FLSH', 'name' => 'Faculte des Lettres et Sciences Humaines'],
            ['code' => 'FSJP', 'name' => 'Faculte des Sciences Juridiques et Politiques'],
            ['code' => 'FSEG', 'name' => 'Faculte des Sciences Economiques et de Gestion'],
            ['code' => 'FSS', 'name' => 'Faculte des Sciences de la Sante'],
        ];

        foreach ($faculties as $faculty) {
            Faculty::firstOrCreate(
                ['code' => $faculty['code']],
                [
                    'name' => $faculty['name'],
                    'dean_id' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
