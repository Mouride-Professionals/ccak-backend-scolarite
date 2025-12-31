<?php

namespace Database\Factories;

use App\Models\AcademicProgram;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicProgram>
 */
class AcademicProgramFactory extends Factory
{
    protected $model = AcademicProgram::class;

    public function definition(): array
    {
        static $sequence = 0;
        $programs = [
            ['name' => 'Licence Informatique', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ['name' => 'Licence Mathematiques', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ['name' => 'Licence Physique', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ['name' => 'Licence Chimie', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ['name' => 'Licence Biologie', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ['name' => 'Licence Economie', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ['name' => 'Licence Gestion', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ['name' => 'Licence Droit Prive', 'level' => AcademicProgram::LEVELS[0], 'duration' => 6, 'credits' => 180],
            ['name' => 'Master Informatique', 'level' => AcademicProgram::LEVELS[1], 'duration' => 4, 'credits' => 120],
            ['name' => 'Master Mathematiques', 'level' => AcademicProgram::LEVELS[1], 'duration' => 4, 'credits' => 120],
            ['name' => 'Master Economie', 'level' => AcademicProgram::LEVELS[1], 'duration' => 4, 'credits' => 120],
            ['name' => 'Master Sante Publique', 'level' => AcademicProgram::LEVELS[1], 'duration' => 4, 'credits' => 120],
            ['name' => 'Doctorat Medecine', 'level' => AcademicProgram::LEVELS[2], 'duration' => 12, 'credits' => 360],
            ['name' => 'Doctorat Pharmacie', 'level' => AcademicProgram::LEVELS[2], 'duration' => 12, 'credits' => 360],
        ];
        $program = $programs[$sequence % count($programs)];
        $sequence++;

        return [
            'department_id' => Department::factory(),
            'name' => $program['name'],
            'level' => $program['level'],
            'duration_semesters' => $program['duration'],
            'total_credits_required' => $program['credits'],
            'is_active' => true,
        ];
    }
}
